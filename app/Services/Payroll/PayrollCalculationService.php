<?php

namespace App\Services\Payroll;

use App\Models\PayrollItem;
use App\Models\PayrollItemLine;
use App\Models\PayrollPeriod;
use App\Models\SalaryAdvance;
use App\Models\Staff;
use App\Models\StaffCommission;
use App\Models\StaffSalaryStructure;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PayrollCalculationService
{
    public function __construct(private readonly CommissionImportService $commissions)
    {
    }

    public function calculate(Staff $staff, PayrollPeriod $period): array
    {
        $structure = StaffSalaryStructure::withoutTenantScope()
            ->where('tenant_id', $period->tenant_id)
            ->where('staff_id', $staff->id)
            ->effectiveFor($period->end_date->toDateString())
            ->latest('effective_from')
            ->first();

        if (! $structure) {
            throw ValidationException::withMessages([
                'staff' => "{$staff->full_name} does not have an active salary structure for this period.",
            ]);
        }

        $basicPay = $this->basicPay($structure, $period);
        $commissionRows = $structure->commission_enabled ? $this->commissions->approvedUnpaidForPeriod($staff, $period) : collect();
        $commissionAmount = (float) $commissionRows->sum('commission_amount');
        $advanceRows = $this->recoverableAdvances($staff, $period);
        $advanceDeduction = (float) $advanceRows->sum('deduction_amount');
        $grossPay = $basicPay + $commissionAmount;
        $totalDeductions = $advanceDeduction;
        $netPay = max(0, $grossPay - $totalDeductions);

        return [
            'structure' => $structure,
            'summary' => [
                'salary_type' => $structure->salary_type,
                'basic_salary_snapshot' => $structure->basic_salary,
                'hourly_rate_snapshot' => $structure->hourly_rate,
                'daily_rate_snapshot' => $structure->daily_rate,
                'commission_enabled_snapshot' => $structure->commission_enabled,
                'basic_pay' => $basicPay,
                'commission_amount' => $commissionAmount,
                'overtime_amount' => 0,
                'allowance_amount' => 0,
                'bonus_amount' => 0,
                'other_earnings' => 0,
                'gross_pay' => $grossPay,
                'deduction_amount' => 0,
                'advance_deduction' => $advanceDeduction,
                'other_deductions' => 0,
                'total_deductions' => $totalDeductions,
                'net_pay' => $netPay,
                'paid_amount' => 0,
                'balance_amount' => $netPay,
            ],
            'lines' => $this->lines($structure, $basicPay, $commissionRows, $advanceRows),
        ];
    }

    private function basicPay(StaffSalaryStructure $structure, PayrollPeriod $period): float
    {
        return match ($structure->salary_type) {
            StaffSalaryStructure::TYPE_DAILY => (float) $structure->daily_rate * $period->start_date->diffInDays($period->end_date->copy()->addDay()),
            StaffSalaryStructure::TYPE_HOURLY => 0.0,
            StaffSalaryStructure::TYPE_COMMISSION_ONLY => 0.0,
            default => (float) $structure->basic_salary,
        };
    }

    private function recoverableAdvances(Staff $staff, PayrollPeriod $period): Collection
    {
        return SalaryAdvance::withoutTenantScope()
            ->where('tenant_id', $period->tenant_id)
            ->where('staff_id', $staff->id)
            ->where('status', SalaryAdvance::STATUS_PAID)
            ->where('outstanding_amount', '>', 0)
            ->where(function ($query) use ($period) {
                $query->whereNull('paid_date')->orWhereDate('paid_date', '<=', $period->end_date);
            })
            ->orderBy('paid_date')
            ->get()
            ->map(function (SalaryAdvance $advance) {
                $outstanding = (float) $advance->outstanding_amount;
                $installment = (float) $advance->installment_amount;
                $advance->deduction_amount = $installment > 0 ? min($installment, $outstanding) : $outstanding;

                return $advance;
            });
    }

    private function lines(StaffSalaryStructure $structure, float $basicPay, Collection $commissionRows, Collection $advanceRows): array
    {
        $lines = [];

        if ($basicPay > 0) {
            $lines[] = [
                'line_type' => PayrollItemLine::TYPE_EARNING,
                'category' => 'basic_salary',
                'description' => 'Basic salary snapshot',
                'amount' => $basicPay,
                'source_type' => StaffSalaryStructure::class,
                'source_id' => $structure->id,
                'snapshot' => [
                    'salary_type' => $structure->salary_type,
                    'basic_salary' => (float) $structure->basic_salary,
                    'daily_rate' => (float) $structure->daily_rate,
                    'hourly_rate' => (float) $structure->hourly_rate,
                ],
            ];
        }

        $commissionRows->each(function (StaffCommission $commission) use (&$lines) {
            $lines[] = [
                'line_type' => PayrollItemLine::TYPE_EARNING,
                'category' => 'staff_commission',
                'description' => 'Approved staff commission',
                'amount' => (float) $commission->commission_amount,
                'source_type' => StaffCommission::class,
                'source_id' => $commission->id,
                'snapshot' => [
                    'commission_id' => $commission->id,
                    'invoice_id' => $commission->invoice_id,
                    'commission_base' => (float) $commission->commission_base,
                    'commission_rate' => (float) $commission->commission_rate,
                    'earned_at' => $commission->earned_at?->toDateTimeString(),
                ],
            ];
        });

        $advanceRows->each(function (SalaryAdvance $advance) use (&$lines) {
            $lines[] = [
                'line_type' => PayrollItemLine::TYPE_DEDUCTION,
                'category' => 'salary_advance',
                'description' => 'Salary advance recovery',
                'amount' => (float) $advance->deduction_amount,
                'source_type' => SalaryAdvance::class,
                'source_id' => $advance->id,
                'snapshot' => [
                    'advance_number' => $advance->advance_number,
                    'outstanding_amount' => (float) $advance->outstanding_amount,
                    'installment_amount' => $advance->installment_amount === null ? null : (float) $advance->installment_amount,
                ],
            ];
        });

        return $lines;
    }
}
