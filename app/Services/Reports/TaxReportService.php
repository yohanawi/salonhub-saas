<?php

namespace App\Services\Reports;

use App\Models\Invoice;
use App\Models\Refund;
use App\Models\Tenant;
use App\Services\Settings\SettingService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Monthly VAT and SSCL working for the salon's return.
 *
 * Figures are fixed to the month an invoice was issued, so a month already filed does not change
 * later: an invoice voided in a later month is reversed in that month, and refunds count as credit
 * notes in the month they were paid out. Gift card sales and tips are outside the scope of tax.
 * This is a working report for the accountant, not a substitute for their review.
 */
class TaxReportService
{
    public function __construct(
        private readonly SalesReportService $sales,
        private readonly SettingService $settings
    ) {
    }

    /**
     * @param  array<int>  $branchIds
     */
    public function build(Tenant $tenant, array $branchIds, CarbonImmutable $month): array
    {
        $timezone = $this->sales->timezone($tenant);
        $start = $month->setTimezone($timezone)->startOfMonth();
        $end = $start->endOfMonth();
        [$rangeStart, $rangeEnd] = $this->sales->range($start, $end, $timezone);

        // Issued in the month and still standing at month end (a later void is reversed later).
        $issued = $this->lines($tenant, $branchIds, $timezone, function ($query) use ($rangeStart, $rangeEnd) {
            $query->whereBetween('sales.issued_at', [$rangeStart, $rangeEnd])
                ->where(fn ($query) => $query->where('sales.status', Invoice::STATUS_ISSUED)
                    ->orWhere(fn ($query) => $query->where('sales.status', Invoice::STATUS_VOID)->where('sales.voided_at', '>', $rangeEnd)));
        });

        // Issued in an earlier month and voided in this one.
        $voided = $this->lines($tenant, $branchIds, $timezone, function ($query) use ($rangeStart, $rangeEnd) {
            $query->where('sales.status', Invoice::STATUS_VOID)
                ->where('sales.issued_at', '<', $rangeStart)
                ->whereBetween('sales.voided_at', [$rangeStart, $rangeEnd]);
        });

        $creditNotes = $this->creditNotes($tenant, $branchIds, $rangeStart, $rangeEnd);
        $inScope = fn (Collection $lines) => $lines->where('item_type', '!=', 'gift_card');

        $rates = $this->byRate($inScope($issued), $inScope($voided), $creditNotes);
        $exempt = [
            'sales' => round((float) $inScope($issued)->where('tax_rate', '<=', 0)->sum('net'), 2),
            'voided' => round((float) $inScope($voided)->where('tax_rate', '<=', 0)->sum('net'), 2),
            'credit_notes' => round((float) $creditNotes->where('tax_rate', '<=', 0)->sum('net'), 2),
        ];
        $exempt['net'] = round($exempt['sales'] - $exempt['voided'] - $exempt['credit_notes'], 2);

        $turnover = fn (Collection $issuedLines, Collection $voidLines, Collection $notes) => round(
            (float) $issuedLines->sum('net') - (float) $voidLines->sum('net') - (float) $notes->sum('net'),
            2
        );
        $serviceTypes = fn (Collection $lines) => $lines->where('item_type', '!=', 'product');
        $productTypes = fn (Collection $lines) => $lines->where('item_type', 'product');

        $ssclRate = (float) $this->settings->get('tax.sscl_rate', $tenant->id);
        $serviceTurnover = $turnover($serviceTypes($inScope($issued)), $serviceTypes($inScope($voided)), $serviceTypes($creditNotes));
        $productTurnover = $turnover($productTypes($issued), $productTypes($voided), $productTypes($creditNotes));

        return [
            'month' => $start,
            'tax_number' => $this->settings->get('business.tax_number', $tenant->id),
            'rates' => $rates,
            'totals' => [
                'taxable' => round((float) $rates->sum('net_taxable'), 2),
                'tax' => round((float) $rates->sum('net_tax'), 2),
            ],
            'exempt' => $exempt,
            'outside_scope' => [
                'gift_cards_sold' => round((float) $issued->where('item_type', 'gift_card')->sum('net'), 2),
                'tips' => round((float) Invoice::withoutTenantScope()
                    ->where('tenant_id', $tenant->id)
                    ->whereIn('branch_id', $branchIds)
                    ->where('status', Invoice::STATUS_ISSUED)
                    ->whereBetween('issued_at', [$rangeStart, $rangeEnd])
                    ->sum('tip_amount'), 2),
            ],
            'sscl' => [
                'enabled' => (bool) $this->settings->get('tax.sscl_enabled', $tenant->id),
                'rate' => $ssclRate,
                'service_turnover' => $serviceTurnover,
                'product_turnover' => $productTurnover,
                'turnover' => round($serviceTurnover + $productTurnover, 2),
                'levy' => round(($serviceTurnover + $productTurnover) * $ssclRate / 100, 2),
            ],
            'invoices' => $issued->groupBy('sale_id')->map(fn (Collection $lines) => [
                'invoice_number' => $lines->first()['invoice_number'],
                'issued_at' => $lines->first()['issued_at'],
                'taxable' => round((float) $lines->where('tax_rate', '>', 0)->sum(fn ($line) => $this->taxable($line)), 2),
                'exempt' => round((float) $inScope($lines)->where('tax_rate', '<=', 0)->sum('net'), 2),
                'tax' => round((float) $lines->sum('tax'), 2),
                'net' => round((float) $lines->sum('net'), 2),
            ])->sortBy('issued_at')->values(),
            'counts' => [
                'invoices' => $issued->pluck('sale_id')->unique()->count(),
                'voided' => $voided->pluck('sale_id')->unique()->count(),
                'credit_notes' => $creditNotes->pluck('refund_id')->unique()->count(),
            ],
        ];
    }

    private function lines(Tenant $tenant, array $branchIds, string $timezone, callable $scope): Collection
    {
        $query = Invoice::withoutTenantScope()
            ->where('sales.tenant_id', $tenant->id)
            ->whereIn('sales.branch_id', $branchIds);

        $scope($query);

        return $this->sales->lines($query, $timezone);
    }

    /**
     * Taxable value of a line, worked back from the tax charged so invoice discounts are reflected.
     */
    private function taxable(array $line): float
    {
        return $line['tax_rate'] > 0 ? round($line['tax'] * 100 / $line['tax_rate'], 2) : 0.0;
    }

    /**
     * Refunds paid out in the month, split back onto invoice lines with their tax.
     */
    private function creditNotes(Tenant $tenant, array $branchIds, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return Refund::withoutTenantScope()
            ->with(['items.invoiceItem', 'invoice.items'])
            ->where('tenant_id', $tenant->id)
            ->whereIn('branch_id', $branchIds)
            ->where('status', Refund::STATUS_COMPLETED)
            ->whereBetween('processed_at', [$start, $end])
            ->get()
            ->flatMap(function (Refund $refund) {
                $invoice = $refund->invoice;

                if (! $invoice) {
                    return [];
                }

                // Item refunds carry their own lines; an amount-only refund is shared over the invoice.
                $parts = $refund->items->isNotEmpty()
                    ? $refund->items->filter(fn ($item) => $item->invoiceItem)->map(fn ($item) => [$item->invoiceItem, (float) $item->amount])
                    : $invoice->items->map(fn ($item) => [$item, $invoice->amountDue() > 0 ? (float) $refund->amount * (float) $item->total_amount / $invoice->amountDue() : 0]);

                return $parts->map(function (array $part) use ($refund, $invoice) {
                    [$item, $amount] = $part;
                    $share = (float) $item->total_amount > 0 ? min(1, $amount / (float) $item->total_amount) : 0;
                    $tax = round((float) $item->tax_amount * $share, 2);

                    return [
                        'refund_id' => $refund->id,
                        'item_type' => $item->item_type,
                        'tax_name' => $invoice->tax_name ?: 'Tax',
                        'tax_rate' => round((float) $item->tax_rate, 4),
                        'tax' => $tax,
                        'net' => round($amount - $tax, 2),
                    ];
                })->filter(fn (array $line) => $line['item_type'] !== 'gift_card');
            })
            ->values();
    }

    private function byRate(Collection $issued, Collection $voided, Collection $creditNotes): Collection
    {
        $key = fn (array $line) => $line['tax_name'] . '|' . rtrim(rtrim(number_format($line['tax_rate'], 4, '.', ''), '0'), '.');
        $taxed = fn (Collection $lines) => $lines->where('tax_rate', '>', 0)->groupBy($key);

        $issuedByRate = $taxed($issued);
        $voidedByRate = $taxed($voided);
        $notesByRate = $taxed($creditNotes);

        return $issuedByRate->keys()->merge($voidedByRate->keys())->merge($notesByRate->keys())->unique()->sort()
            ->map(function (string $rateKey) use ($issuedByRate, $voidedByRate, $notesByRate) {
                [$name, $rate] = explode('|', $rateKey);
                $sum = fn (Collection $lines, callable|string $field) => round((float) $lines->sum($field), 2);
                $issued = $issuedByRate[$rateKey] ?? collect();
                $voided = $voidedByRate[$rateKey] ?? collect();
                $notes = $notesByRate[$rateKey] ?? collect();
                $taxable = fn (array $line) => $this->taxable($line);

                $row = [
                    'name' => $name,
                    'rate' => (float) $rate,
                    'taxable' => $sum($issued, $taxable),
                    'tax' => $sum($issued, 'tax'),
                    'voided_taxable' => $sum($voided, $taxable),
                    'voided_tax' => $sum($voided, 'tax'),
                    'credit_taxable' => $sum($notes, $taxable),
                    'credit_tax' => $sum($notes, 'tax'),
                ];

                $row['net_taxable'] = round($row['taxable'] - $row['voided_taxable'] - $row['credit_taxable'], 2);
                $row['net_tax'] = round($row['tax'] - $row['voided_tax'] - $row['credit_tax'], 2);

                return $row;
            })
            ->values();
    }
}
