<?php

namespace App\Services\Reports;

use App\Models\AppointmentDeposit;
use App\Models\GiftCard;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Staff;
use App\Models\StaffCommission;
use App\Models\StaffTip;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Sales by month, staff, service, product and payment method for a date range.
 *
 * Line values are net of tax and include each line's share of invoice-level discounts (manual
 * discount, loyalty redemption), so the breakdowns add up to the invoice totals. Gift cards are
 * vouchers, not revenue: their sale is reported separately and the value counts when it is spent.
 */
class SalesReportService
{
    public const REVENUE_TYPES = ['service', 'product', 'package', 'membership'];

    /**
     * @param  array<int>  $branchIds
     */
    public function build(Tenant $tenant, array $branchIds, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $timezone = $this->timezone($tenant);
        $range = $this->range($from, $to, $timezone);
        $lines = $this->lines($this->issuedInvoices($tenant, $branchIds)->whereBetween('sales.issued_at', $range), $timezone);
        $revenue = $lines->whereIn('item_type', self::REVENUE_TYPES);
        $invoiceIds = $lines->pluck('sale_id')->unique()->values();

        $tips = StaffTip::withoutTenantScope()
            ->with('staff')
            ->whereIn('invoice_id', $invoiceIds)
            ->where('status', '!=', StaffTip::STATUS_VOID)
            ->get();

        $refunds = Refund::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->whereIn('branch_id', $branchIds)
            ->where('status', Refund::STATUS_COMPLETED)
            ->whereBetween('processed_at', $range)
            ->get(['id', 'amount', 'processed_at']);

        $payments = Payment::withoutTenantScope()
            ->leftJoin('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
            ->where('payments.tenant_id', $tenant->id)
            ->whereIn('payments.branch_id', $branchIds)
            ->where('payments.status', Payment::STATUS_COMPLETED)
            ->whereBetween('payments.paid_at', $range)
            ->groupBy('payment_methods.name', 'payment_methods.type')
            ->selectRaw("COALESCE(payment_methods.name, 'Other') as name, COALESCE(payment_methods.type, 'other') as type, COUNT(*) as count, SUM(payments.amount) as total")
            ->orderBy('name')
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'type' => $row->type, 'count' => (int) $row->count, 'total' => round((float) $row->total, 2)]);

        return [
            'from' => $from,
            'to' => $to,
            'summary' => [
                'invoices' => $invoiceIds->count(),
                'gross' => $this->sum($revenue, 'gross'),
                'discounts' => round($this->sum($revenue, 'gross') - $this->sum($revenue, 'net'), 2),
                'package_credits' => $this->sum($revenue, 'package_value'),
                'net_sales' => $this->sum($revenue, 'net'),
                'tax' => $this->sum($revenue, 'tax'),
                'tips' => round((float) $tips->sum('amount'), 2),
                'refunds' => round((float) $refunds->sum('amount'), 2),
                'gift_cards_sold' => $this->sum($lines->where('item_type', 'gift_card'), 'net'),
            ],
            'months' => $this->byMonth($revenue, $tips, $refunds, $timezone),
            'staff' => $this->byStaff($tenant, $revenue, $tips, $invoiceIds),
            'services' => $this->byItem($revenue->where('item_type', 'service'), 'service_id'),
            'products' => $this->byItem($revenue->where('item_type', 'product'), 'product_id'),
            'payments' => $payments,
            'prepaid' => $this->prepaid($tenant, $branchIds, $range),
        ];
    }

    /**
     * Invoices that count as sales: issued, not voided.
     */
    public function issuedInvoices(Tenant $tenant, array $branchIds): Builder
    {
        return Invoice::withoutTenantScope()
            ->where('sales.tenant_id', $tenant->id)
            ->whereIn('sales.branch_id', $branchIds)
            ->where('sales.status', Invoice::STATUS_ISSUED);
    }

    /**
     * One row per invoice line, with tax removed and invoice-level discounts shared out by value.
     */
    public function lines(Builder $invoices, string $timezone): Collection
    {
        $rows = InvoiceItem::withoutTenantScope()
            ->joinSub($invoices->select(['sales.id', 'sales.issued_at', 'sales.total', 'sales.tax', 'sales.tax_name', 'sales.invoice_number']), 'invoice', 'invoice.id', '=', 'sale_items.sale_id')
            ->get([
                'sale_items.id',
                'sale_items.sale_id',
                'sale_items.item_type',
                'sale_items.service_id',
                'sale_items.product_id',
                'sale_items.staff_id',
                'sale_items.item_name',
                'sale_items.quantity',
                'sale_items.unit_price',
                'sale_items.gross_amount',
                'sale_items.discount_amount',
                'sale_items.tax_rate',
                'sale_items.tax_amount',
                'sale_items.total_amount',
                'sale_items.package_quantity',
                'sale_items.package_discount_amount',
                'invoice.issued_at as invoice_issued_at',
                'invoice.total as invoice_total',
                'invoice.tax as invoice_tax',
                'invoice.tax_name as invoice_tax_name',
                'invoice.invoice_number as invoice_number',
            ]);

        return $rows->groupBy('sale_id')->flatMap(function (Collection $items) use ($timezone) {
            $first = $items->first();
            $invoiceNet = max(0, (float) $first->invoice_total - (float) $first->invoice_tax);
            $rawNets = $items->map(fn ($item) => max(0, (float) $item->total_amount - (float) $item->tax_amount));
            $rawTotal = $rawNets->sum();
            $allocated = 0.0;
            $lastKey = $items->keys()->last();

            return $items->map(function ($item, $key) use ($rawNets, $rawTotal, $invoiceNet, &$allocated, $lastKey, $timezone) {
                // The last line takes the rounding remainder so lines add up to the invoice exactly.
                $net = $rawTotal > 0
                    ? ($key === $lastKey ? round($invoiceNet - $allocated, 2) : round($invoiceNet * $rawNets[$key] / $rawTotal, 2))
                    : 0.0;
                $allocated += $net;

                return [
                    'id' => $item->id,
                    'sale_id' => $item->sale_id,
                    'invoice_number' => $item->invoice_number,
                    'issued_at' => CarbonImmutable::parse($item->invoice_issued_at)->setTimezone($timezone),
                    'item_type' => $item->item_type,
                    'service_id' => $item->service_id,
                    'product_id' => $item->product_id,
                    'staff_id' => $item->staff_id,
                    'name' => $item->item_name ?: 'Item',
                    'quantity' => (int) $item->quantity,
                    'gross' => round((float) $item->gross_amount, 2),
                    'net' => $net,
                    'tax' => round((float) $item->tax_amount, 2),
                    'tax_rate' => round((float) $item->tax_rate, 4),
                    'tax_name' => $item->invoice_tax_name ?: 'Tax',
                    'package_quantity' => (int) $item->package_quantity,
                    'package_value' => round((float) $item->package_discount_amount, 2),
                ];
            });
        })->values();
    }

    public function timezone(Tenant $tenant): string
    {
        return $tenant->timezone ?: config('app.timezone');
    }

    /**
     * Salon-local calendar days converted to the stored (app) timezone.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function range(CarbonImmutable $from, CarbonImmutable $to, string $timezone): array
    {
        return [
            $from->setTimezone($timezone)->startOfDay()->setTimezone(config('app.timezone')),
            $to->setTimezone($timezone)->endOfDay()->setTimezone(config('app.timezone')),
        ];
    }

    private function byMonth(Collection $revenue, Collection $tips, Collection $refunds, string $timezone): Collection
    {
        $tipsByMonth = $tips->groupBy(fn (StaffTip $tip) => $tip->created_at->setTimezone($timezone)->format('Y-m'));
        $refundsByMonth = $refunds->groupBy(fn (Refund $refund) => $refund->processed_at->setTimezone($timezone)->format('Y-m'));

        return $revenue
            ->groupBy(fn (array $line) => $line['issued_at']->format('Y-m'))
            ->map(fn (Collection $lines, string $month) => [
                'month' => $month,
                'label' => CarbonImmutable::createFromFormat('Y-m', $month)->format('F Y'),
                'invoices' => $lines->pluck('sale_id')->unique()->count(),
                'services' => $this->sum($lines->where('item_type', 'service'), 'net'),
                'products' => $this->sum($lines->where('item_type', 'product'), 'net'),
                'other' => $this->sum($lines->whereIn('item_type', ['package', 'membership']), 'net'),
                'net_sales' => $this->sum($lines, 'net'),
                'tax' => $this->sum($lines, 'tax'),
                'tips' => round((float) ($tipsByMonth[$month] ?? collect())->sum('amount'), 2),
                'refunds' => round((float) ($refundsByMonth[$month] ?? collect())->sum('amount'), 2),
            ])
            ->sortKeys()
            ->values();
    }

    private function byStaff(Tenant $tenant, Collection $revenue, Collection $tips, Collection $invoiceIds): Collection
    {
        $commissions = StaffCommission::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->whereIn('invoice_id', $invoiceIds)
            ->whereNotIn('status', [StaffCommission::STATUS_REVERSED, StaffCommission::STATUS_CANCELLED])
            ->groupBy('staff_id')
            ->selectRaw('staff_id, SUM(commission_amount) as total')
            ->pluck('total', 'staff_id');

        $tipsByStaff = $tips->groupBy('staff_id');
        $linesByStaff = $revenue->whereNotNull('staff_id')->groupBy('staff_id');
        $staffIds = $linesByStaff->keys()->merge($tipsByStaff->keys())->unique();
        $staff = Staff::withoutTenantScope()->withTrashed()->whereIn('id', $staffIds)->get()->keyBy('id');

        return $staffIds
            ->map(function ($staffId) use ($linesByStaff, $tipsByStaff, $commissions, $staff) {
                $lines = $linesByStaff[$staffId] ?? collect();
                $services = $lines->where('item_type', 'service');

                return [
                    'staff_id' => (int) $staffId,
                    'name' => $staff[$staffId]?->full_name ?? 'Staff #' . $staffId,
                    'services_count' => (int) $services->sum('quantity'),
                    'service_sales' => $this->sum($services, 'net'),
                    'product_sales' => $this->sum($lines->where('item_type', 'product'), 'net'),
                    'total_sales' => $this->sum($lines, 'net'),
                    'tips' => round((float) ($tipsByStaff[$staffId] ?? collect())->sum('amount'), 2),
                    'commission' => round((float) ($commissions[$staffId] ?? 0), 2),
                ];
            })
            ->sortByDesc('total_sales')
            ->values();
    }

    private function byItem(Collection $lines, string $key): Collection
    {
        return $lines
            ->groupBy(fn (array $line) => $line[$key] ?: 'name:' . $line['name'])
            ->map(fn (Collection $group) => [
                'name' => $group->first()['name'],
                'quantity' => (int) $group->sum('quantity'),
                'package_quantity' => (int) $group->sum('package_quantity'),
                'gross' => $this->sum($group, 'gross'),
                'net' => $this->sum($group, 'net'),
                'average' => $group->sum('quantity') > 0 ? round($this->sum($group, 'net') / $group->sum('quantity'), 2) : 0,
            ])
            ->sortByDesc('net')
            ->values();
    }

    /**
     * Prepaid money the salon holds: gift card value and appointment deposits.
     */
    private function prepaid(Tenant $tenant, array $branchIds, array $range): array
    {
        $deposits = AppointmentDeposit::withoutTenantScope()->where('tenant_id', $tenant->id)->whereIn('branch_id', $branchIds);

        return [
            'gift_cards_redeemed' => round((float) Payment::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->whereIn('branch_id', $branchIds)
                ->whereNotNull('gift_card_id')
                ->where('status', Payment::STATUS_COMPLETED)
                ->whereBetween('paid_at', $range)
                ->sum('amount'), 2),
            'gift_card_liability' => round((float) GiftCard::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->where('status', GiftCard::STATUS_ACTIVE)
                ->sum('balance'), 2),
            'deposits_received' => round((float) (clone $deposits)->whereBetween('received_at', $range)->sum('amount'), 2),
            'deposits_forfeited' => round((float) (clone $deposits)->where('status', AppointmentDeposit::STATUS_FORFEITED)->whereBetween('resolved_at', $range)->sum('amount'), 2),
            'deposits_held' => round((float) (clone $deposits)->where('status', AppointmentDeposit::STATUS_HELD)->get()->sum(fn (AppointmentDeposit $deposit) => $deposit->remainingAmount()), 2),
        ];
    }

    private function sum(Collection $lines, string $field): float
    {
        return round((float) $lines->sum($field), 2);
    }
}
