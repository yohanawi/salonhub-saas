<?php

namespace App\Services\Billing;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Staff;
use App\Models\StaffTip;
use App\Models\User;
use App\Services\StaffAvailabilityService;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Tips are collected with the invoice payment and passed on to staff through payroll. They are kept
 * out of the invoice total so they never count as revenue, tax, commission or loyalty spend.
 */
class TipService
{
    public function __construct(private readonly StaffAvailabilityService $staffAvailability)
    {
    }

    /**
     * @return Collection<int, array{staff_id: int, amount: float}>
     */
    public function linesFromInput(Branch $branch, array $lines): Collection
    {
        return collect($lines)
            ->filter(fn ($line) => is_array($line) && (float) ($line['amount'] ?? 0) > 0)
            ->map(function (array $line, int|string $index) use ($branch) {
                $staff = Staff::withoutTenantScope()
                    ->where('tenant_id', $branch->tenant_id)
                    ->where('status', Staff::STATUS_ACTIVE)
                    ->find($line['staff_id'] ?? null);

                if (! $staff || ! $this->staffAvailability->canWorkAtBranch($staff, $branch)) {
                    throw ValidationException::withMessages([
                        "tips.{$index}.staff_id" => 'Select the staff member at ' . $branch->name . ' who receives this tip.',
                    ]);
                }

                return ['staff_id' => $staff->id, 'amount' => round((float) $line['amount'], 2)];
            })
            ->groupBy('staff_id')
            ->map(fn (Collection $group, int $staffId) => ['staff_id' => $staffId, 'amount' => round((float) $group->sum('amount'), 2)])
            ->values();
    }

    public function recordForInvoice(Invoice $invoice, Collection $lines, User $user): void
    {
        $lines->each(fn (array $line) => StaffTip::create([
            'tenant_id' => $invoice->tenant_id,
            'branch_id' => $invoice->branch_id,
            'invoice_id' => $invoice->id,
            'staff_id' => $line['staff_id'],
            'amount' => $line['amount'],
            'status' => StaffTip::STATUS_PENDING,
            'created_by' => $user->id,
        ]));
    }

    /**
     * A tip is only owed to staff once the customer has actually paid the invoice in full.
     */
    public function markEarned(Invoice $invoice): void
    {
        StaffTip::withoutTenantScope()
            ->where('invoice_id', $invoice->id)
            ->where('status', StaffTip::STATUS_PENDING)
            ->update(['status' => StaffTip::STATUS_EARNED, 'earned_at' => now()]);
    }

    public function voidForInvoice(Invoice $invoice): void
    {
        StaffTip::withoutTenantScope()
            ->where('invoice_id', $invoice->id)
            ->whereIn('status', [StaffTip::STATUS_PENDING, StaffTip::STATUS_EARNED])
            ->update(['status' => StaffTip::STATUS_VOID]);
    }

    /**
     * Earned tips in the period that are not already part of another open payroll run.
     */
    public function earnedUnpaidForPeriod(Staff $staff, PayrollPeriod $period): Collection
    {
        return StaffTip::withoutTenantScope()
            ->where('tenant_id', $period->tenant_id)
            ->where('staff_id', $staff->id)
            ->where('status', StaffTip::STATUS_EARNED)
            ->whereNull('paid_at')
            ->whereBetween('earned_at', [$period->start_date->copy()->startOfDay(), $period->end_date->copy()->endOfDay()])
            ->when($period->branch_id, fn ($query) => $query->where('branch_id', $period->branch_id))
            ->whereNotExists(function (QueryBuilder $sub) {
                $sub->selectRaw('1')
                    ->from('payroll_item_lines')
                    ->join('payroll_items', 'payroll_items.id', '=', 'payroll_item_lines.payroll_item_id')
                    ->join('payroll_runs', 'payroll_runs.id', '=', 'payroll_items.payroll_run_id')
                    ->where('payroll_item_lines.source_type', StaffTip::class)
                    ->whereColumn('payroll_item_lines.source_id', 'staff_tips.id')
                    ->whereIn('payroll_runs.status', PayrollRun::OPEN_STATUSES);
            })
            ->orderBy('earned_at')
            ->get();
    }
}
