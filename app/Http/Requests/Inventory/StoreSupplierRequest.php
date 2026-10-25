<?php

namespace App\Http\Requests\Inventory;

use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        $supplier = $this->route('supplier');

        return $supplier
            ? ($this->user()?->can('update', $supplier) ?? false)
            : ($this->user()?->can('create', Supplier::class) ?? false);
    }

    public function rules(): array
    {
        $supplier = $this->route('supplier');
        $tenantId = $supplier?->tenant_id
            ?? ($this->user()->hasRole('Super Admin') ? $this->integer('tenant_id') : $this->user()->tenant_id);

        return [
            'tenant_id' => [$this->user()->hasRole('Super Admin') && ! $supplier ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('suppliers', 'name')->where('tenant_id', $tenantId)->ignore($supplier?->id),
            ],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:1000'],
            'tax_number' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
