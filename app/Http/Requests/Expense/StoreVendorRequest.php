<?php

namespace App\Http\Requests\Expense;

use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Vendor::class) ?? false;
    }

    public function rules(): array
    {
        $tenantId = $this->user()?->hasRole('Super Admin') ? $this->input('tenant_id') : $this->user()?->tenant_id;

        return [
            'tenant_id' => [$this->user()?->hasRole('Super Admin') ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('vendors', 'name')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:3000'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
