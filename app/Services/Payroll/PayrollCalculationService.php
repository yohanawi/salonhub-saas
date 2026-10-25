<?php

namespace App\Services\Payroll;

use App\Models\PayrollItem;
use App\Models\PayrollItemLine;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\SalaryAdvance;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\StaffCommission;
use App\Models\StaffSalaryStructure;
use App\Models\StaffTip;
use App\Services\Billing\TipService;
use App\Services\Settings\SettingService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PayrollCalculationService
{
    public function __construct(
        private readonly CommissionImportService $commissions,
        private readonly TipService $tips,
        private readonly SettingService $settings,
    ) {
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

        $attendance = $this->attendance($staff, $period);
        $basicPay = $this->basicPay($structure, $period, $attendance);
        $overtime = $this->overtime($structure, $period, $attendance);
        // Commission is paid here only when both the salon (Settings > Team) and the salary structure allow it.
        $payCommission = $structure->commission_enabled && $this->settings->get('payroll.commission_included', $period->tenant_id);
        $commissionRows = $payCommission ? $this->commissions->approvedUnpaidForPeriod($staff, $period) : collect();
        $commissionAmount = (float) $commissionRows->sum('commission_amount');
        // Tips belong to the staff member whatever their salary type, so they are always paid out.
        $tipRows = $this->tips->earnedUnpaidForPeriod($staff, $period);
        $tipAmount = round((float) $tipRows->sum('amount'), 2);
        $grossPay = $basicPay + $overtime['amount'] + $commissionAmount + $tipAmount;
        $statutory = $this->statutoryContributions($structure, $period, $basicPay, $commissionAmount);
        // EPF is taken first; advances are then recovered only from what is left.
        $advanceRows = $this->recoverableAdvances($staff, $period, max(0, $grossPay - $statutory['epf_employee']));
        $advanceDeduction = (float) $advanceRows->sum('deduction_amount');
        $totalDeductions = round($advanceDeduction + $statutory['epf_employee'], 2);
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
                'tip_amount' => $tipAmount,
                'overtime_amount' => $overtime['amount'],
                'allowance_amount' => 0,
                'bonus_amount' => 0,
                'other_earnings' => 0,
                'gross_pay' => $grossPay,
                'statutory_earnings' => $statutory['earnings'],
                'deduction_amount' => $statutory['epf_employee'],
                'advance_deduction' => $advanceDeduction,
                'epf_employee_amount' => $statutory['epf_employee'],
                'epf_employer_amount' => $statutory['epf_employer'],
                'etf_employer_amount' => $statutory['etf_employer'],
                'other_deductions' => 0,
                'total_deductions' => $totalDeductions,
                'net_pay' => $netPay,
                'paid_amount' => 0,
                'balance_amount' => $netPay,
            ],
            'lines' => $this->lines($structure, $basicPay, $commissionRows, $advanceRows, $tipRows, $statutory, $overtime),
        ];
    }

    /**
     * Sri Lankan EPF (employee 8% deducted, employer 12%) and ETF (employer 3%), on basic pay and optionally
     * commission. Rates come from Settings > Team > Payroll; staff can be excluded on their salary structure.
     *
     * @return array{earnings: float, epf_employee: float, epf_employer: float, etf_employer: float, rates: array}
     */
    public function statutoryContributions(StaffSalaryStructure $structure, PayrollPeriod $period, float $basicPay, float $commissionAmount): array
    {
        $setting = fn (string $key) => $this->settings->get($key, $period->tenant_id, $period->branch_id);
        $none = ['earnings' => 0.0, 'epf_employee' => 0.0, 'epf_employer' => 0.0, 'etf_employer' => 0.0, 'rates' => []];

        if (! $setting('payroll.epf_enabled') || ! $structure->epf_applicable) {
            return $none;
        }

        $earnings = round($basicPay + ($setting('payroll.epf_include_commission') ? $commissionAmount : 0), 2);

        if ($earnings <= 0) {
            return $none;
        }

        $rates = [
            'epf_employee' => (float) ($setting('payroll.epf_employee_rate') ?? 8),
            'epf_employer' => (float) ($setting('payroll.epf_employer_rate') ?? 12),
            'etf_employer' => (float) ($setting('payroll.etf_employer_rate') ?? 3),
        ];

        return [
            'earnings' => $earnings,
            'epf_employee' => round($earnings * $rates['epf_employee'] / 100, 2),
            'epf_employer' => round($earnings * $rates['epf_employer'] / 100, 2),
            'etf_employer' => round($earnings * $rates['etf_employer'] / 100, 2),
            'rates' => $rates,
        ];
    }

    /**
     * @param  array{worked_minutes: int, overtime_minutes: int}  $attendance
     */
    private function basicPay(StaffSalaryStructure $structure, PayrollPeriod $period, array $attendance): float
    {
        return match ($structure->salary_type) {
            StaffSalaryStructure::TYPE_DAILY => (float) $structure->daily_rate * $period->start_date->diffInDays($period->end_date->copy()->addDay()),
            // Hourly staff are paid for the hours they clocked; any overtime premium is added separately.
            StaffSalaryStructure::TYPE_HOURLY => round(($attendance['worked_minutes'] - $this->paidOvertimeMinutes($period, $attendance)) / 60 * (float) $structure->hourly_rate, 2),
            StaffSalaryStructure::TYPE_COMMISSION_ONLY => 0.0,
            default => (float) $structure->basic_salary,
        };
    }

    /**
     * Clocked time in the period, from completed attendance records. Overtime is time beyond the rostered
     * shift on days that had one.
     *
     * @return array{worked_minutes: int, overtime_minutes: int}
     */
    private function attendance(Staff $staff, PayrollPeriod $period): array
    {
        $records = StaffAttendance::withoutTenantScope()
            ->where('tenant_id', $period->tenant_id)
            ->where('staff_id', $staff->id)
            ->whereBetween('work_date', [$period->start_date->toDateString(), $period->end_date->toDateString()])
            ->whereNotNull('worked_minutes')
            ->get();

        return [
            'worked_minutes' => (int) $records->sum('worked_minutes'),
            'overtime_minutes' => (int) $records->sum(function (StaffAttendance $record) {
                if (! $record->scheduled_start || ! $record->scheduled_end) {
                    return 0;
                }

                $scheduled = Carbon::parse($record->scheduled_start)->diffInMinutes(Carbon::parse($record->scheduled_end)) - (int) $record->break_minutes;

                return max(0, (int) $record->worked_minutes - max(0, $scheduled));
            }),
        ];
    }

    /**
     * @param  array{worked_minutes: int, overtime_minutes: int}  $attendance
     */
    private function paidOvertimeMinutes(PayrollPeriod $period, array $attendance): int
    {
        return $this->settings->get('payroll.overtime_enabled', $period->tenant_id) ? $attendance['overtime_minutes'] : 0;
    }

    /**
     * Overtime at the salon's rate (Settings > Team > Payroll), as a multiple of the hourly pay. Monthly
     * salaries use the Sri Lankan convention of salary / 240 for the hourly figure.
     *
     * @param  array{worked_minutes: int, overtime_minutes: int}  $attendance
     * @return array{minutes: int, hourly_rate: float, multiplier: float, amount: float}
     */
    private function overtime(StaffSalaryStructure $structure, PayrollPeriod $period, array $attendance): array
    {
        $minutes = $this->paidOvertimeMinutes($period, $attendance);
        $hourlyRate = match (true) {
            (float) $structure->hourly_rate > 0 => (float) $structure->hourly_rate,
            (float) $structure->basic_salary > 0 => (float) $structure->basic_salary / 240,
            (float) $structure->daily_rate > 0 => (float) $structure->daily_rate / 8,
            default => 0.0,
        };
        $multiplier = (float) $this->settings->get('payroll.overtime_rate', $period->tenant_id);

        return [
            'minutes' => $minutes,
            'hourly_rate' => round($hourlyRate, 2),
            'multiplier' => $multiplier,
            'amount' => $structure->salary_type === StaffSalaryStructure::TYPE_COMMISSION_ONLY
                ? 0.0
                : round($minutes / 60 * $hourlyRate * $multiplier, 2),
        ];
    }

    private function recoverableAdvances(Staff $staff, PayrollPeriod $period, float $grossPay): Collection
    {
        // Recoveries already scheduled in other unpaid runs are not yet reflected in outstanding_amount.
        $pendingRecoveries = PayrollItemLine::withoutTenantScope()
            ->where('payroll_item_lines.tenant_id', $period->tenant_id)
            ->where('payroll_item_lines.source_type', SalaryAdvance::class)
            ->join('payroll_items', 'payroll_items.id', '=', 'payroll_item_lines.payroll_item_id')
            ->join('payroll_runs', 'payroll_runs.id', '=', 'payroll_items.payroll_run_id')
            ->whereIn('payroll_runs.status', PayrollRun::OPEN_STATUSES)
            ->groupBy('payroll_item_lines.source_id')
            ->selectRaw('payroll_item_lines.source_id, sum(payroll_item_lines.amount) as pending')
            ->pluck('pending', 'source_id');

        // Deductions can never exceed what is earned in this run; the remainder stays outstanding for later runs.
        $remainingPay = $grossPay;

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
            ->map(function (SalaryAdvance $advance) use ($pendingRecoveries, &$remainingPay) {
                $outstanding = max(0, (float) $advance->outstanding_amount - (float) ($pendingRecoveries[$advance->id] ?? 0));
                $installment = (float) $advance->installment_amount;
                $deduction = $installment > 0 ? min($installment, $outstanding) : $outstanding;
                $deduction = round(min($deduction, max(0, $remainingPay)), 2);

                $remainingPay -= $deduction;
                $advance->deduction_amount = $deduction;

                return $advance;
            })
            ->filter(fn (SalaryAdvance $advance) => $advance->deduction_amount > 0)
            ->values();
    }

    private function lines(StaffSalaryStructure $structure, float $basicPay, Collection $commissionRows, Collection $advanceRows, Collection $tipRows, array $statutory = [], array $overtime = []): array
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

        if (($overtime['amount'] ?? 0) > 0) {
            $hours = round($overtime['minutes'] / 60, 2);
            $lines[] = [
                'line_type' => PayrollItemLine::TYPE_EARNING,
                'category' => 'overtime',
                'description' => "Overtime: {$hours} h × " . number_format($overtime['hourly_rate'], 2) . " × {$overtime['multiplier']}",
                'amount' => $overtime['amount'],
                'source_type' => StaffSalaryStructure::class,
                'source_id' => $structure->id,
                'snapshot' => $overtime,
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

        $tipRows->each(function (StaffTip $tip) use (&$lines) {
            $lines[] = [
                'line_type' => PayrollItemLine::TYPE_EARNING,
                'category' => 'staff_tip',
                'description' => 'Customer tip',
                'amount' => (float) $tip->amount,
                'source_type' => StaffTip::class,
                'source_id' => $tip->id,
                'snapshot' => [
                    'tip_id' => $tip->id,
                    'invoice_id' => $tip->invoice_id,
                    'earned_at' => $tip->earned_at?->toDateTimeString(),
                ],
            ];
        });

        if (($statutory['epf_employee'] ?? 0) > 0) {
            $lines[] = [
                'line_type' => PayrollItemLine::TYPE_DEDUCTION,
                'category' => 'epf_employee',
                'description' => 'EPF employee contribution (' . rtrim(rtrim(number_format($statutory['rates']['epf_employee'], 2), '0'), '.') . '%)',
                'amount' => $statutory['epf_employee'],
                'source_type' => StaffSalaryStructure::class,
                'source_id' => $structure->id,
                'snapshot' => [
                    'epf_number' => $structure->epf_number,
                    'statutory_earnings' => $statutory['earnings'],
                    'rates' => $statutory['rates'],
                ],
            ];
        }

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
