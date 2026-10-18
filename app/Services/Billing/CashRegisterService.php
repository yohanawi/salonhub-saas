<?php

namespace App\Services\Billing;

use App\Models\Branch;
use App\Models\CashRegisterMovement;
use App\Models\CashRegisterShift;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Refund;
use App\Models\User;
use App\Services\Settings\SettingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * One drawer per branch: at most one shift is open per branch, and every payment or refund taken
 * at that branch while it is open is attached to it.
 */
class CashRegisterService
{
    /** Notes and coins in circulation, largest first, for counting the drawer by denomination. */
    private const DENOMINATIONS = [
        'LKR' => [5000, 2000, 1000, 500, 100, 50, 20, 10, 5, 2, 1],
    ];

    public function __construct(private readonly SettingService $settings)
    {
    }

    /**
     * @return array<int, int|float>
     */
    public function denominationsFor(?string $currency): array
    {
        return self::DENOMINATIONS[strtoupper((string) $currency)] ?? [];
    }

    /**
     * Locks the branch's open shift for a cash expense paid out of the drawer, refusing when no shift
     * is open or the drawer is not expected to hold that much.
     */
    public function shiftForDrawerExpense(int $branchId, float $amount): CashRegisterShift
    {
        $shift = CashRegisterShift::withoutTenantScope()
            ->where('branch_id', $branchId)
            ->where('status', CashRegisterShift::STATUS_OPEN)
            ->lockForUpdate()
            ->first();

        if (! $shift) {
            throw ValidationException::withMessages(['from_cash_drawer' => 'Open a cash register shift at this branch before paying from the drawer.']);
        }

        if ($amount > $this->summary($shift)['expected_cash'] + 0.009) {
            throw ValidationException::withMessages(['from_cash_drawer' => 'The cash drawer is not expected to hold that much.']);
        }

        return $shift;
    }

    public function openShiftFor(int $branchId): ?CashRegisterShift
    {
        return CashRegisterShift::withoutTenantScope()
            ->where('branch_id', $branchId)
            ->where('status', CashRegisterShift::STATUS_OPEN)
            ->latest('opened_at')
            ->first();
    }

    public function requiresOpenShift(int $tenantId, int $branchId): bool
    {
        return (bool) $this->settings->get('pos.require_open_shift', $tenantId, $branchId);
    }

    /**
     * The shift a cash or card transaction at this branch belongs to. Cash is refused when the salon
     * requires a counted drawer and none is open, because it could not be reconciled later.
     */
    public function shiftForTransaction(int $tenantId, int $branchId, PaymentMethod $method, string $field = 'payment_method_id'): ?CashRegisterShift
    {
        $shift = $this->openShiftFor($branchId);

        if (! $shift && $method->type === PaymentMethod::TYPE_CASH && $this->requiresOpenShift($tenantId, $branchId)) {
            throw ValidationException::withMessages([
                $field => 'Open a cash register shift before taking or refunding cash.',
            ]);
        }

        return $shift;
    }

    public function open(Branch $branch, float $openingCash, ?string $notes, User $user): CashRegisterShift
    {
        return DB::transaction(function () use ($branch, $openingCash, $notes, $user) {
            // Locking the branch serialises concurrent "open" clicks so only one drawer opens.
            Branch::withoutTenantScope()->whereKey($branch->id)->lockForUpdate()->first();

            if ($this->openShiftFor($branch->id)) {
                throw ValidationException::withMessages(['shift' => "A shift is already open at {$branch->name}. Close it before opening a new one."]);
            }

            $shift = CashRegisterShift::create([
                'tenant_id' => $branch->tenant_id,
                'branch_id' => $branch->id,
                'status' => CashRegisterShift::STATUS_OPEN,
                'opening_cash' => round(max(0, $openingCash), 2),
                'opened_by' => $user->id,
                'opened_at' => now(),
                'opening_notes' => $notes,
            ]);

            $shift->update(['shift_number' => 'SHIFT-' . $shift->opened_at->format('Y') . '-' . str_pad((string) $shift->id, 6, '0', STR_PAD_LEFT)]);

            return $shift;
        });
    }

    public function recordMovement(CashRegisterShift $shift, string $type, float $amount, string $reason, User $user): CashRegisterMovement
    {
        return DB::transaction(function () use ($shift, $type, $amount, $reason, $user) {
            $shift = CashRegisterShift::withoutTenantScope()->whereKey($shift->id)->lockForUpdate()->firstOrFail();

            if (! $shift->isOpen()) {
                throw ValidationException::withMessages(['shift' => 'Cash can only be moved on an open shift.']);
            }

            if ($type === CashRegisterMovement::TYPE_CASH_OUT && $amount > $this->summary($shift)['expected_cash']) {
                throw ValidationException::withMessages(['amount' => 'There is not that much cash expected in the drawer.']);
            }

            return $shift->movements()->create([
                'tenant_id' => $shift->tenant_id,
                'type' => $type,
                'amount' => round($amount, 2),
                'reason' => $reason,
                'created_by' => $user->id,
            ]);
        });
    }

    /**
     * @param  array<string|int, int|string|null>  $denominations  count per note/coin value; when any
     *                                                              count is given it decides the counted total
     */
    public function close(CashRegisterShift $shift, ?float $countedCash, array $denominations, ?string $notes, User $user): CashRegisterShift
    {
        return DB::transaction(function () use ($shift, $countedCash, $denominations, $notes, $user) {
            $shift = CashRegisterShift::withoutTenantScope()->with(['branch', 'tenant'])->whereKey($shift->id)->lockForUpdate()->firstOrFail();

            if (! $shift->isOpen()) {
                throw ValidationException::withMessages(['shift' => 'This shift is already closed.']);
            }

            $allowed = $this->denominationsFor($shift->branch?->currency ?: $shift->tenant?->currency);
            $counts = collect($allowed)
                ->mapWithKeys(fn ($value) => [(string) $value => max(0, (int) ($denominations[(string) $value] ?? 0))])
                ->filter(fn (int $count) => $count > 0);

            if ($counts->isNotEmpty()) {
                $countedCash = (float) $counts->map(fn (int $count, int|string $value) => $count * (float) $value)->sum();
            }

            if ($countedCash === null) {
                throw ValidationException::withMessages(['counted_cash' => 'Enter the cash counted in the drawer.']);
            }

            $summary = $this->summary($shift);
            $countedCash = round(max(0, $countedCash), 2);

            // The summary is frozen at close, so later voids cannot change a shift that was already counted.
            $shift->update([
                'status' => CashRegisterShift::STATUS_CLOSED,
                'expected_cash' => $summary['expected_cash'],
                'counted_cash' => $countedCash,
                'counted_denominations' => $counts->isNotEmpty() ? $counts->all() : null,
                'cash_difference' => round($countedCash - $summary['expected_cash'], 2),
                'summary' => $summary,
                'closed_by' => $user->id,
                'closed_at' => now(),
                'closing_notes' => $notes,
            ]);

            return $shift->fresh(['branch', 'openedBy', 'closedBy']);
        });
    }

    /**
     * Live totals for an open shift, or the snapshot taken when it closed.
     *
     * @return array{opening_cash: float, payments: array, refunds: array, cash_sales: float, cash_refunds: float, cash_in: float, cash_out: float, cash_expenses: float, expected_cash: float, payments_total: float, refunds_total: float, transactions: int}
     */
    public function summary(CashRegisterShift $shift): array
    {
        if (! $shift->isOpen() && is_array($shift->summary)) {
            return $shift->summary;
        }

        $payments = Payment::withoutTenantScope()
            ->leftJoin('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
            ->where('payments.cash_register_shift_id', $shift->id)
            ->where('payments.status', Payment::STATUS_COMPLETED)
            ->groupBy('payment_methods.name', 'payment_methods.type')
            ->selectRaw("COALESCE(payment_methods.name, 'Other') as name, COALESCE(payment_methods.type, 'other') as type, COUNT(*) as count, SUM(payments.amount) as total")
            ->get();

        $refunds = Refund::withoutTenantScope()
            ->leftJoin('payment_methods', 'refunds.payment_method_id', '=', 'payment_methods.id')
            ->where('refunds.cash_register_shift_id', $shift->id)
            ->where('refunds.status', Refund::STATUS_COMPLETED)
            ->groupBy('payment_methods.name', 'payment_methods.type')
            ->selectRaw("COALESCE(payment_methods.name, 'Other') as name, COALESCE(payment_methods.type, 'other') as type, COUNT(*) as count, SUM(refunds.amount) as total")
            ->get();

        $movements = $shift->movements()->selectRaw('type, SUM(amount) as total')->groupBy('type')->pluck('total', 'type');

        $rows = fn ($collection) => $collection
            ->map(fn ($row) => ['name' => $row->name, 'type' => $row->type, 'count' => (int) $row->count, 'total' => round((float) $row->total, 2)])
            ->sortBy('name')
            ->values()
            ->all();

        $cashSales = round((float) $payments->where('type', PaymentMethod::TYPE_CASH)->sum('total'), 2);
        $cashRefunds = round((float) $refunds->where('type', PaymentMethod::TYPE_CASH)->sum('total'), 2);
        $cashIn = round((float) ($movements[CashRegisterMovement::TYPE_CASH_IN] ?? 0), 2);
        $cashOut = round((float) ($movements[CashRegisterMovement::TYPE_CASH_OUT] ?? 0), 2);
        $cashExpenses = round((float) $shift->expensePayments()->sum('amount'), 2);

        return [
            'opening_cash' => (float) $shift->opening_cash,
            'payments' => $rows($payments),
            'refunds' => $rows($refunds),
            'cash_sales' => $cashSales,
            'cash_refunds' => $cashRefunds,
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'cash_expenses' => $cashExpenses,
            'expected_cash' => round((float) $shift->opening_cash + $cashSales - $cashRefunds + $cashIn - $cashOut - $cashExpenses, 2),
            'payments_total' => round((float) $payments->sum('total'), 2),
            'refunds_total' => round((float) $refunds->sum('total'), 2),
            'transactions' => (int) $payments->sum('count'),
        ];
    }
}
