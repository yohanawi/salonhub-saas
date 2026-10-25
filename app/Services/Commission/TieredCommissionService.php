<?php

namespace App\Services\Commission;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Staff;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Tiered ("target based") commission: the rate for a sale depends on how much the staff member has sold
 * so far in that calendar month, counting the sale itself. Each sale keeps the rate of the tier reached when
 * it was paid; reaching a higher tier later in the month does not re-rate earlier sales.
 */
class TieredCommissionService
{
    /** Line types that count towards a staff member's sales target. */
    public const SALES_ITEM_TYPES = ['service', 'product'];

    /**
     * Net (after discount, before tax) service and product sales credited to the staff member in the month of
     * $at. With $upToItem, only sales paid before it (plus earlier lines of the same invoice) are counted.
     */
    public function monthlySales(Staff $staff, CarbonInterface $at, ?InvoiceItem $upToItem = null, ?Invoice $upToInvoice = null): float
    {
        $from = Carbon::parse($at)->startOfMonth();
        $to = Carbon::parse($at)->endOfMonth();

        $query = InvoiceItem::withoutTenantScope()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sale_items.tenant_id', $staff->tenant_id)
            ->where('sale_items.staff_id', $staff->id)
            ->whereIn('sale_items.item_type', self::SALES_ITEM_TYPES)
            ->where('sales.status', '!=', Invoice::STATUS_VOID)
            ->where('sales.payment_status', Invoice::PAYMENT_PAID)
            ->whereBetween('sales.paid_at', [$from, $to]);

        if ($upToItem && $upToInvoice) {
            $paidAt = $upToInvoice->paid_at ?? now();

            $query->where(function ($query) use ($paidAt, $upToInvoice, $upToItem) {
                $query->where(function ($query) use ($paidAt, $upToInvoice) {
                    $query->where('sales.paid_at', '<', $paidAt)->where('sales.id', '!=', $upToInvoice->id);
                })->orWhere(function ($query) use ($upToInvoice, $upToItem) {
                    $query->where('sales.id', $upToInvoice->id)->where('sale_items.id', '<=', $upToItem->id);
                });
            });
        }

        return round((float) $query->sum('sale_items.net_amount'), 2);
    }

    /**
     * The tier whose threshold the sales figure has reached (tiers sorted by min_sales ascending).
     *
     * @param  array<int, array{min_sales: float, rate: float}>  $tiers
     * @return array{min_sales: float, rate: float, index: int}|null
     */
    public function tierFor(array $tiers, float $sales): ?array
    {
        $reached = null;

        foreach (array_values($tiers) as $index => $tier) {
            if ($sales + 0.00001 >= (float) $tier['min_sales']) {
                $reached = ['min_sales' => (float) $tier['min_sales'], 'rate' => (float) $tier['rate'], 'index' => $index];
            }
        }

        return $reached;
    }

    /**
     * Progress towards the next tier, for dashboards.
     *
     * @return array{sales: float, current: ?array, next: ?array, remaining: ?float, percent: int}
     */
    public function progress(array $tiers, float $sales): array
    {
        $tiers = array_values($tiers);
        $current = $this->tierFor($tiers, $sales);
        $nextIndex = $current === null ? 0 : $current['index'] + 1;
        $next = $tiers[$nextIndex] ?? null;

        $floor = $current['min_sales'] ?? 0.0;
        $percent = 100;

        if ($next) {
            $span = max(0.01, (float) $next['min_sales'] - $floor);
            $percent = (int) max(0, min(100, floor(($sales - $floor) / $span * 100)));
        }

        return [
            'sales' => $sales,
            'current' => $current,
            'next' => $next ? ['min_sales' => (float) $next['min_sales'], 'rate' => (float) $next['rate']] : null,
            'remaining' => $next ? max(0, round((float) $next['min_sales'] - $sales, 2)) : null,
            'percent' => $percent,
        ];
    }
}
