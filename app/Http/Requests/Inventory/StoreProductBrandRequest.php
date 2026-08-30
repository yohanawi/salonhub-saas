<?php

namespace App\Http\Requests\Inventory;

use App\Models\ProductBrand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ProductBrand::class) ?? false;
    }

    public function rules(): array
    {
        $tenantId = $this->user()?->hasRole('Super Admin') ? $this->input('tenant_id') : $this->user()?->tenant_id;

        return [
            'tenant_id' => [$this->user()?->hasRole('Super Admin') ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('product_brands', 'name')->where('tenant_id', $tenantId)],
            'description' => ['nullable', 'string', 'max:3000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
