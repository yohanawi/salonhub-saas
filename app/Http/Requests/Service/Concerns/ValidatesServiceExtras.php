<?php

namespace App\Http\Requests\Service\Concerns;

use App\Models\Product;
use App\Models\Service;
use Illuminate\Validation\Validator;

/**
 * Online listing, add-on and consumable fields shared by the create and edit service forms.
 */
trait ValidatesServiceExtras
{
    protected function extraRules(): array
    {
        return [
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'online_description' => ['nullable', 'string', 'max:3000'],
            'show_online' => ['nullable', 'boolean'],
            'is_addon' => ['nullable', 'boolean'],
            'addons_present' => ['nullable', 'boolean'],
            'consumables_present' => ['nullable', 'boolean'],
            'addon_ids' => ['nullable', 'array'],
            'addon_ids.*' => ['integer', 'distinct'],
            'consumables' => ['nullable', 'array'],
            'consumables.*.product_id' => ['nullable', 'integer'],
            'consumables.*.quantity' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }

    protected function validateExtras(Validator $validator, ?int $tenantId, ?int $serviceId = null): void
    {
        $addonIds = collect($this->input('addon_ids', []))->map(fn ($id) => (int) $id)->filter();

        if ($serviceId && $addonIds->contains($serviceId)) {
            $validator->errors()->add('addon_ids', 'A service cannot be an add-on of itself.');
        }

        if ($addonIds->isNotEmpty()
            && Service::query()->where('tenant_id', $tenantId)->whereIn('id', $addonIds)->count() !== $addonIds->count()) {
            $validator->errors()->add('addon_ids', 'One or more add-on services do not belong to this salon.');
        }

        $productIds = collect($this->input('consumables', []))
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->filter();

        if ($productIds->duplicates()->isNotEmpty()) {
            $validator->errors()->add('consumables', 'Each product can only be listed once as a consumable.');
        }

        if ($productIds->isNotEmpty()
            && Product::query()->where('tenant_id', $tenantId)->whereIn('id', $productIds->unique())->count() !== $productIds->unique()->count()) {
            $validator->errors()->add('consumables', 'One or more consumable products do not belong to this salon.');
        }
    }
}
