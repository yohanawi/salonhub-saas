<?php

namespace App\Services\Payroll;

use App\Models\Branch;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollRunService
{
    public function __construct(private readonly PayrollCalculationService $calculator)
    {
    }

    public function createPeriod(Tenant $tenant, array $data, User $user): PayrollPeriod
    {
        $branch = $this->branch($tenant, $data['branch_id'] ?? null);

        return PayrollPeriod::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch?->id,
            'name' => $data['name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'pay_date' => $data['pay_date'] ?? null,
            'status' => $data['status'] ?? PayrollPeriod::STATUS_OPEN,
            'created_by' => $user->id,
        ]);
    }

    public function generate(PayrollPeriod $period, User $user): PayrollRun
    {
        if (in_array($period->status, [PayrollPeriod::STATUS_APPROVED, PayrollPeriod::STATUS_PAID, PayrollPeriod::STATUS_CLOSED, PayrollPeriod::STATUS_CANCELLED], true)) {
            throw ValidationException::withMessages(['period' => 'This payroll period is locked.']);
        }

        return DB::transaction(function () use ($period, $user) {
            $period->update(['status' => PayrollPeriod::STATUS_PROCESSING]);

            $run = PayrollRun::create([
                'tenant_id' => $period->tenant_id,
                'branch_id' => $period->branch_id,
                'payroll_period_id' => $period->id,
                'run_number' => 'PAY-TMP-' . uniqid(),
                'status' => PayrollRun::STATUS_DRAFT,
                'created_by' => $user->id,
                'calculated_by' => $user->id,
                'calculated_at' => now(),
            ]);

            $run->update(['run_number' => $this->runNumber($run)]);

            $staffQuery = Staff::withoutTenantScope()
                ->where('tenant_id', $period->tenant_id)
                ->whereIn('status', [Staff::STATUS_ACTIVE, Staff::STATUS_ON_LEAVE]);

            if ($period->branch_id) {
                $staffQuery->whereHas('branches', fn ($query) => $query->where('branches.id', $period->branch_id));
            }

            $staffQuery->orderBy('first_name')->get()->each(function (Staff $staff) use ($period, $run, $user) {
                $calculation = $this->calculator->calculate($staff, $period);
                $structure = $calculation['structure'];
                $summary = $calculation['summary'];

                $item = PayrollItem::create($summary + [
                    'tenant_id' => $period->tenant_id,
                    'branch_id' => $period->branch_id ?: $structure->branch_id ?: $staff->primaryBranch()?->id,
                    'payroll_period_id' => $period->id,
                    'payroll_run_id' => $run->id,
                    'staff_id' => $staff->id,
                    'salary_structure_id' => $structure->id,
                    'status' => PayrollItem::STATUS_CALCULATED,
                    'payment_status' => PayrollRun::PAYMENT_UNPAID,
                ]);

                foreach ($calculation['lines'] as $line) {
                    $item->lines()->create($line + [
                        'tenant_id' => $period->tenant_id,
                        'created_by' => $user->id,
                    ]);
                }
            });

            $this->refreshTotals($run);
            $run->update(['status' => PayrollRun::STATUS_CALCULATED]);
            $period->update(['status' => PayrollPeriod::STATUS_CALCULATED]);

            return $run->fresh(['period', 'items.staff', 'items.lines']);
        });
    }

    public function refreshTotals(PayrollRun $run): PayrollRun
    {
        $items = $run->items()->get();
        $netPay = (float) $items->sum('net_pay');
        $paid = (float) $items->sum('paid_amount');

        $run->update([
            'employees_count' => $items->count(),
            'basic_salary_total' => $items->sum('basic_pay'),
            'commission_total' => $items->sum('commission_amount'),
            'earnings_total' => $items->sum(fn (PayrollItem $item) => (float) $item->allowance_amount + (float) $item->bonus_amount + (float) $item->other_earnings),
            'deductions_total' => $items->sum('total_deductions'),
            'gross_pay' => $items->sum('gross_pay'),
            'total_deductions' => $items->sum('total_deductions'),
            'net_pay' => $netPay,
            'paid_amount' => $paid,
            'balance_amount' => max(0, $netPay - $paid),
            'payment_status' => $paid <= 0 ? PayrollRun::PAYMENT_UNPAID : ($paid < $netPay ? PayrollRun::PAYMENT_PARTIAL : PayrollRun::PAYMENT_PAID),
        ]);

        return $run->fresh();
    }

    private function branch(Tenant $tenant, mixed $branchId): ?Branch
    {
        if (! $branchId) {
            return null;
        }

        return Branch::withoutTenantScope()->where('tenant_id', $tenant->id)->findOrFail($branchId);
    }

    private function runNumber(PayrollRun $run): string
    {
        return 'PAY-' . now()->format('Y') . '-' . str_pad((string) $run->id, 6, '0', STR_PAD_LEFT);
    }
}
