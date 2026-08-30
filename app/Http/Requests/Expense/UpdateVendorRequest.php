<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('vendor')) ?? false;
    }

    public function rules(): array
    {
        $vendor = $this->route('vendor');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('vendors', 'name')->where('tenant_id', $vendor->tenant_id)->whereNull('deleted_at')->ignore($vendor->id)],
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
