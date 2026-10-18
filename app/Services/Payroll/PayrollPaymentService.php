<?php

namespace App\Services\Payroll;

use App\Models\PayrollItem;
use App\Models\PayrollItemLine;
use App\Models\PayrollPayment;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\SalaryAdvance;
use App\Models\StaffCommission;
use App\Models\StaffTip;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollPaymentService
{
    public function payRun(PayrollRun $run, array $data, User $user): PayrollRun
    {
        return DB::transaction(function () use ($run, $data, $user) {
            // Lock the run so a double submit cannot record every salary payment twice.
            $run = PayrollRun::withoutTenantScope()->whereKey($run->getKey())->lockForUpdate()->firstOrFail();

            if ($run->status !== PayrollRun::STATUS_APPROVED) {
                throw ValidationException::withMessages(['run' => 'Only approved payroll can be paid.']);
            }

            $commissionIds = PayrollItemLine::withoutTenantScope()
                ->whereIn('payroll_item_id', $run->items()->pluck('id'))
                ->where('source_type', StaffCommission::class)
                ->pluck('source_id');

            $staleCommissions = StaffCommission::withoutTenantScope()
                ->whereIn('id', $commissionIds)
                ->where(fn ($query) => $query->where('status', '!=', StaffCommission::STATUS_APPROVED)->orWhereNotNull('paid_at'))
                ->exists();

            $tipIds = PayrollItemLine::withoutTenantScope()
                ->whereIn('payroll_item_id', $run->items()->pluck('id'))
                ->where('source_type', StaffTip::class)
                ->pluck('source_id');

            $staleCommissions = $staleCommissions || StaffTip::withoutTenantScope()
                ->whereIn('id', $tipIds)
                ->where(fn ($query) => $query->where('status', '!=', StaffTip::STATUS_EARNED)->orWhereNotNull('paid_at'))
                ->exists();

            if ($staleCommissions) {
                throw ValidationException::withMessages([
                    'run' => 'Some commissions or tips in this payroll were reversed or paid elsewhere after it was generated. Regenerate the payroll before paying it.',
                ]);
            }

            $run->items()->where('payment_status', '!=', PayrollRun::PAYMENT_PAID)->get()->each(function (PayrollItem $item) use ($run, $data, $user) {
                if ((float) $item->balance_amount <= 0) {
                    return;
                }

                $payment = PayrollPayment::create([
                    'tenant_id' => $run->tenant_id,
                    'branch_id' => $item->branch_id,
                    'payroll_run_id' => $run->id,
                    'payroll_item_id' => $item->id,
                    'staff_id' => $item->staff_id,
                    'payment_number' => 'PAYROLL-PMT-TMP-' . uniqid(),
                    'amount' => $item->balance_amount,
                    'payment_method' => $data['payment_method'],
                    'payment_reference' => $data['payment_reference'] ?? null,
                    'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                    'status' => PayrollPayment::STATUS_PAID,
                    'paid_by' => $user->id,
                    'notes' => $data['notes'] ?? null,
                ]);

                $payment->update(['payment_number' => $this->paymentNumber($payment)]);

                $item->update([
                    'paid_amount' => $item->net_pay,
                    'balance_amount' => 0,
                    'payment_status' => PayrollRun::PAYMENT_PAID,
                    'status' => PayrollItem::STATUS_PAID,
                    'paid_by' => $user->id,
                    'paid_at' => now(),
                ]);

                $commissionIds = $item->lines()
                    ->where('source_type', StaffCommission::class)
                    ->pluck('source_id');

                StaffCommission::withoutTenantScope()
                    ->where('tenant_id', $run->tenant_id)
                    ->whereIn('id', $commissionIds)
                    ->update([
                        'status' => StaffCommission::STATUS_PAID,
                        'paid_by' => $user->id,
                        'paid_at' => now(),
                    ]);

                StaffTip::withoutTenantScope()
                    ->where('tenant_id', $run->tenant_id)
                    ->whereIn('id', $item->lines()->where('source_type', StaffTip::class)->pluck('source_id'))
                    ->update([
                        'status' => StaffTip::STATUS_PAID,
                        'paid_by' => $user->id,
                        'paid_at' => now(),
                    ]);

                $item->lines()
                    ->where('source_type', SalaryAdvance::class)
                    ->get()
                    ->each(function ($line) use ($run) {
                        $advance = SalaryAdvance::withoutTenantScope()
                            ->where('tenant_id', $run->tenant_id)
                            ->whereKey($line->source_id)
                            ->first();

                        if (! $advance) {
                            return;
                        }

                        $recovered = min((float) $advance->paid_amount, (float) $advance->recovered_amount + (float) $line->amount);
                        $outstanding = max(0, (float) $advance->paid_amount - $recovered);

                        $advance->update([
                            'recovered_amount' => $recovered,
                            'outstanding_amount' => $outstanding,
                            'status' => $outstanding <= 0 ? SalaryAdvance::STATUS_RECOVERED : SalaryAdvance::STATUS_PAID,
                        ]);
                    });
            });

            app(PayrollRunService::class)->refreshTotals($run);

            $run->update([
                'status' => PayrollRun::STATUS_PAID,
                'payment_status' => PayrollRun::PAYMENT_PAID,
                'paid_by' => $user->id,
                'paid_at' => now(),
            ]);

            $run->period?->update(['status' => PayrollPeriod::STATUS_PAID]);

            return $run->fresh(['items.payments']);
        });
    }

    private function paymentNumber(PayrollPayment $payment): string
    {
        return 'PAYROLL-PMT-' . $payment->payment_date->format('Y') . '-' . str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);
    }
}
