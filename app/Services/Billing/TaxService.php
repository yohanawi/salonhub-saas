<?php

namespace App\Services\Billing;

use App\Models\Branch;
use App\Services\Settings\SettingService;
use Illuminate\Support\Collection;

class TaxService
{
    public function __construct(private readonly SettingService $settings)
    {
    }

    /**
     * Branch tax configuration wins over the salon-wide tax settings.
     *
     * @return array{enabled: bool, name: ?string, rate: float, inclusive: bool}
     */
    public function configFor(Branch $branch): array
    {
        $inclusive = (bool) $this->settings->get('tax.prices_include_tax', $branch->tenant_id, $branch->id);

        if ($branch->tax_enabled && (float) $branch->tax_rate > 0) {
            return [
                'enabled' => true,
                'name' => $branch->tax_name ?: 'Tax',
                'rate' => (float) $branch->tax_rate,
                'inclusive' => $inclusive,
            ];
        }

        $rate = (float) $this->settings->get('tax.rate', $branch->tenant_id, $branch->id);
        $enabled = (bool) $this->settings->get('tax.enabled', $branch->tenant_id, $branch->id) && $rate > 0;

        return [
            'enabled' => $enabled,
            'name' => $enabled ? ($this->settings->get('tax.name', $branch->tenant_id, $branch->id) ?: 'Tax') : null,
            'rate' => $enabled ? $rate : 0.0,
            'inclusive' => $inclusive,
        ];
    }

    /**
     * Tax is charged on what the customer actually pays, so line discounts and a share of the
     * invoice-level discount are taken off before the rate is applied. Each item may carry its
     * own `tax_rate` (products); otherwise the branch/salon rate is used.
     *
     * @return array{items: Collection, tax: float}
     */
    public function applyToItemSnapshots(Collection $items, array $config, float $invoiceDiscount = 0): array
    {
        $items = $items->values()->map(function (array $item) use ($config) {
            $item['tax_rate'] = (float) ($item['tax_rate'] ?? 0) > 0 ? (float) $item['tax_rate'] : (float) $config['rate'];

            return $item;
        });

        $bases = $items->map(fn (array $item) => max(0, (float) $item['gross_amount'] - (float) ($item['discount_amount'] ?? 0)));
        $baseTotal = $bases->sum();
        $invoiceDiscount = min(max(0, $invoiceDiscount), $baseTotal);
        $allocated = 0.0;
        $lastIndex = $items->keys()->last();

        $items = $items->map(function (array $item, int $index) use ($bases, $baseTotal, $invoiceDiscount, &$allocated, $lastIndex, $config) {
            $base = $bases[$index];
            $share = $baseTotal > 0
                ? ($index === $lastIndex ? $invoiceDiscount - $allocated : round($invoiceDiscount * $base / $baseTotal, 2))
                : 0;
            $allocated += $share;

            $taxable = max(0, $base - $share);
            $rate = (float) $item['tax_rate'];
            $tax = match (true) {
                $rate <= 0 => 0.0,
                $config['inclusive'] => round($taxable - ($taxable / (1 + $rate / 100)), 2),
                default => round($taxable * $rate / 100, 2),
            };
            $net = $config['inclusive'] ? $base : $base + $tax;

            $item['tax_amount'] = $tax;
            $item['net_amount'] = $net;
            $item['total'] = $net;
            $item['total_amount'] = $net;

            return $item;
        });

        return [
            'items' => $items,
            'tax' => round($items->sum('tax_amount'), 2),
        ];
    }
}
