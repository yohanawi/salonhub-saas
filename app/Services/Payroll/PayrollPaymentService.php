<?php

namespace App\Services\Payroll;

use App\Models\PayrollItem;
use App\Models\PayrollPayment;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\SalaryAdvance;
use App\Models\StaffCommission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollPaymentService
{
    public function payRun(PayrollRun $run, array $data, User $user): PayrollRun
    {
        if ($run->status !== PayrollRun::STATUS_APPROVED) {
            throw ValidationException::withMessages(['run' => 'Only approved payroll can be paid.']);
        }

        return DB::transaction(function () use ($run, $data, $user) {
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
