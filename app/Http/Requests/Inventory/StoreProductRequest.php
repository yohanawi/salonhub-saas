<?php

namespace App\Http\Requests\Inventory;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    public function rules(): array
    {
        $tenantId = $this->user()?->hasRole('Super Admin') ? $this->input('tenant_id') : $this->user()?->tenant_id;

        return [
            'tenant_id' => [$this->user()?->hasRole('Super Admin') ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
            'category_id' => ['nullable', 'integer'],
            'brand_id' => ['nullable', 'integer'],
            'unit_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', Rule::unique('products', 'sku')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'barcode' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'product_type' => ['required', Rule::in(Product::TYPES)],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'track_inventory' => ['nullable', 'boolean'],
            'allow_negative_stock' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'minimum_stock_level' => ['nullable', 'integer', 'min:0'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'opening_branch_id' => ['nullable', 'integer'],
            'opening_quantity' => ['nullable', 'integer', 'min:0'],
            'opening_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
