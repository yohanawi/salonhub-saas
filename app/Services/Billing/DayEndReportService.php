<?php

namespace App\Services\Billing;

use App\Models\CashRegisterShift;
use App\Models\ExpensePayment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DayEndReportService
{
    /**
     * The business day is the salon's local calendar day; timestamps are stored in the app timezone,
     * so the day's bounds are converted before querying.
     *
     * @param  array<int>  $branchIds
     */
    public function build(Tenant $tenant, array $branchIds, CarbonImmutable $day): array
    {
        $timezone = $tenant->timezone ?: config('app.timezone');
        $start = $day->setTimezone($timezone)->startOfDay();
        $range = [$start->setTimezone(config('app.timezone')), $start->endOfDay()->setTimezone(config('app.timezone'))];

        $issued = Invoice::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->whereIn('branch_id', $branchIds)
            ->where('status', Invoice::STATUS_ISSUED)
            ->whereBetween('issued_at', $range);

        $sales = (clone $issued)->selectRaw('COUNT(*) as invoices, COALESCE(SUM(subtotal), 0) as subtotal, COALESCE(SUM(discount), 0) as discount, COALESCE(SUM(promotion_discount_amount), 0) as promotion, COALESCE(SUM(membership_discount_amount), 0) as membership, COALESCE(SUM(loyalty_redemption_amount), 0) as loyalty, COALESCE(SUM(tax), 0) as tax, COALESCE(SUM(total), 0) as total')->first();

        $voided = Invoice::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->whereIn('branch_id', $branchIds)
            ->where('status', Invoice::STATUS_VOID)
            ->whereBetween('voided_at', $range)
            ->selectRaw('COUNT(*) as invoices, COALESCE(SUM(total), 0) as total')
            ->first();

        $itemTypes = InvoiceItem::withoutTenantScope()
            ->whereIn('sale_id', (clone $issued)->select('id'))
            ->groupBy('item_type')
            ->selectRaw('item_type, SUM(quantity) as quantity, SUM(total_amount) as total')
            ->get()
            ->map(fn ($row) => ['type' => $row->item_type, 'quantity' => (int) $row->quantity, 'total' => round((float) $row->total, 2)])
            ->values();

        $payments = $this->byMethod(
            Payment::withoutTenantScope()
                ->leftJoin('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
                ->where('payments.tenant_id', $tenant->id)
                ->whereIn('payments.branch_id', $branchIds)
                ->where('payments.status', Payment::STATUS_COMPLETED)
                ->whereBetween('payments.paid_at', $range),
            'payments.amount'
        );

        $refunds = $this->byMethod(
            Refund::withoutTenantScope()
                ->leftJoin('payment_methods', 'refunds.payment_method_id', '=', 'payment_methods.id')
                ->where('refunds.tenant_id', $tenant->id)
                ->whereIn('refunds.branch_id', $branchIds)
                ->where('refunds.status', Refund::STATUS_COMPLETED)
                ->whereBetween('refunds.processed_at', $range),
            'refunds.amount'
        );

        $shifts = CashRegisterShift::withoutTenantScope()
            ->with(['branch', 'openedBy', 'closedBy'])
            ->where('tenant_id', $tenant->id)
            ->whereIn('branch_id', $branchIds)
            ->where('opened_at', '<=', $range[1])
            ->where(fn (Builder $query) => $query->whereNull('closed_at')->orWhere('closed_at', '>=', $range[0]))
            ->orderBy('opened_at')
            ->get();

        $drawerExpenses = (float) ExpensePayment::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->whereIn('branch_id', $branchIds)
            ->whereNotNull('cash_register_shift_id')
            ->whereBetween('created_at', $range)
            ->sum('amount');

        $discount = round((float) $sales->discount, 2);
        $promotion = round((float) $sales->promotion, 2);
        $membership = round((float) $sales->membership, 2);
        $collected = round($payments->sum('total'), 2);
        $refunded = round($refunds->sum('total'), 2);

        return [
            'day' => $start,
            'sales' => [
                'invoices' => (int) $sales->invoices,
                'subtotal' => round((float) $sales->subtotal, 2),
                'discount' => $discount,
                'promotion_discount' => $promotion,
                'membership_discount' => $membership,
                'other_discount' => round(max(0, $discount - $promotion - $membership), 2),
                'loyalty_redemption' => round((float) $sales->loyalty, 2),
                'tax' => round((float) $sales->tax, 2),
                'total' => round((float) $sales->total, 2),
            ],
            'item_types' => $itemTypes,
            'voided' => ['invoices' => (int) $voided->invoices, 'total' => round((float) $voided->total, 2)],
            'payments' => $payments,
            'refunds' => $refunds,
            'collected' => $collected,
            'refunded' => $refunded,
            'net_collected' => round($collected - $refunded, 2),
            'drawer_expenses' => round($drawerExpenses, 2),
            'shifts' => $shifts,
            'cash_difference' => round((float) $shifts->where('status', CashRegisterShift::STATUS_CLOSED)->sum('cash_difference'), 2),
        ];
    }

    private function byMethod(Builder $query, string $amountColumn): Collection
    {
        return $query
            ->groupBy('payment_methods.name', 'payment_methods.type')
            ->selectRaw("COALESCE(payment_methods.name, 'Other') as name, COALESCE(payment_methods.type, 'other') as type, COUNT(*) as count, SUM({$amountColumn}) as total")
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'type' => $row->type, 'count' => (int) $row->count, 'total' => round((float) $row->total, 2)])
            ->sortBy('name')
            ->values();
    }
}
