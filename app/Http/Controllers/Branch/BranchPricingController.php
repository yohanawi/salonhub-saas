<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\Product;
use App\Models\Service;
use App\Models\Setting;
use App\Services\Settings\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * One branch's own price list (service prices/durations, retail prices) and its tax settings.
 * Anything left blank falls back to the salon-wide default.
 */
class BranchPricingController extends Controller
{
    public const TAX_KEYS = ['tax.enabled', 'tax.name', 'tax.rate', 'tax.prices_include_tax'];

    public function edit(Request $request, Branch $branch, SettingService $settings): View
    {
        $this->authorize('update', $branch);

        $services = Service::withoutTenantScope()
            ->with(['category', 'branches' => fn ($query) => $query->where('branches.id', $branch->id)])
            ->where('tenant_id', $branch->tenant_id)
            ->orderBy('name')
            ->get();

        $products = Product::withoutTenantScope()
            ->where('tenant_id', $branch->tenant_id)
            ->where('is_sellable', true)
            ->orderBy('name')
            ->get();

        $branchTaxRows = Setting::withoutTenantScope()
            ->where('tenant_id', $branch->tenant_id)
            ->where('branch_id', $branch->id)
            ->whereIn('key', self::TAX_KEYS)
            ->exists();

        return view('pages/apps.branch-management.branches.pricing', [
            'branch' => $branch,
            'services' => $services,
            'products' => $products,
            'productPrices' => BranchProduct::withoutTenantScope()->where('branch_id', $branch->id)->pluck('selling_price', 'product_id'),
            'tax' => $this->effectiveTax($branch, $settings),
            'salonTax' => collect(self::TAX_KEYS)->mapWithKeys(fn (string $key) => [$key => $settings->get($key, $branch->tenant_id)])->all(),
            // The branch form's own tax fields (branches.tax_*) also count as "own tax".
            'hasOwnTax' => $branchTaxRows || ($branch->tax_enabled && (float) $branch->tax_rate > 0),
            'canManageTax' => $request->user()->hasRole('Super Admin') || $request->user()->can('settings.tax.manage'),
            'currency' => $branch->currency ?: ($branch->tenant?->currency ?: 'LKR'),
        ]);
    }

    /**
     * What this branch charges today, whether it comes from the branch form's tax fields or settings.
     */
    private function effectiveTax(Branch $branch, SettingService $settings): array
    {
        $config = app(\App\Services\Billing\TaxService::class)->configFor($branch);

        return [
            'tax.enabled' => $config['enabled'],
            'tax.name' => $config['name'] ?? $settings->get('tax.name', $branch->tenant_id, $branch->id),
            'tax.rate' => $config['rate'],
            'tax.prices_include_tax' => $config['inclusive'],
        ];
    }

    public function update(Request $request, Branch $branch, SettingService $settings): RedirectResponse
    {
        $this->authorize('update', $branch);

        $data = $request->validate([
            'services' => ['nullable', 'array'],
            'services.*.enabled' => ['nullable', 'boolean'],
            'services.*.price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'services.*.duration_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'products' => ['nullable', 'array'],
            'products.*' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'tax_mode' => ['nullable', 'in:salon,branch'],
            'tax_enabled' => ['nullable', 'boolean'],
            'tax_name' => ['nullable', 'string', 'max:50'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_prices_include_tax' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($branch, $data, $request, $settings) {
            $serviceIds = Service::withoutTenantScope()->where('tenant_id', $branch->tenant_id)->pluck('id')->all();

            foreach ($data['services'] ?? [] as $serviceId => $row) {
                if (! in_array((int) $serviceId, $serviceIds, true)) {
                    continue;
                }

                $service = Service::withoutTenantScope()->find($serviceId);

                if (empty($row['enabled'])) {
                    $service->branches()->detach($branch->id);
                    continue;
                }

                $service->branches()->syncWithoutDetaching([$branch->id => [
                    'tenant_id' => $branch->tenant_id,
                    'price' => ($row['price'] ?? '') === '' ? null : $row['price'],
                    'duration_minutes' => ($row['duration_minutes'] ?? '') === '' ? null : $row['duration_minutes'],
                    'is_active' => true,
                ]]);
            }

            $productIds = Product::withoutTenantScope()->where('tenant_id', $branch->tenant_id)->pluck('id')->all();

            foreach ($data['products'] ?? [] as $productId => $price) {
                if (! in_array((int) $productId, $productIds, true)) {
                    continue;
                }

                if ($price === null || $price === '') {
                    BranchProduct::withoutTenantScope()->where('branch_id', $branch->id)->where('product_id', $productId)->delete();
                    continue;
                }

                BranchProduct::withoutTenantScope()->updateOrCreate(
                    ['branch_id' => $branch->id, 'product_id' => (int) $productId],
                    ['tenant_id' => $branch->tenant_id, 'selling_price' => $price]
                );
            }

            $canManageTax = $request->user()->hasRole('Super Admin') || $request->user()->can('settings.tax.manage');

            if ($canManageTax && in_array($data['tax_mode'] ?? null, ['salon', 'branch'], true)) {
                // This page is now the one place branch tax is kept, so the legacy branch columns are cleared.
                $branch->update(['tax_enabled' => false, 'tax_rate' => null]);
            }

            if ($canManageTax && ($data['tax_mode'] ?? null) === 'salon') {
                Setting::withoutTenantScope()
                    ->where('tenant_id', $branch->tenant_id)
                    ->where('branch_id', $branch->id)
                    ->whereIn('key', self::TAX_KEYS)
                    ->delete();
                $settings->forgetCache($branch->tenant_id, $branch->id);
            } elseif ($canManageTax && ($data['tax_mode'] ?? null) === 'branch') {
                $settings->setMany($branch->tenant, $branch, [
                    'tax.enabled' => (bool) ($data['tax_enabled'] ?? false),
                    'tax.name' => $data['tax_name'] ?? 'VAT',
                    'tax.rate' => $data['tax_rate'] ?? 0,
                    'tax.prices_include_tax' => (bool) ($data['tax_prices_include_tax'] ?? false),
                ], $request->user(), $request);
            }
        });

        return redirect()->route('branches.pricing.edit', $branch)->with('status', 'Branch price list and tax settings saved.');
    }
}
