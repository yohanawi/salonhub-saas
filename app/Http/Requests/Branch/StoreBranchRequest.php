<?php

namespace App\Http\Requests\Branch;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Branch::class) ?? false;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('branches', 'code')->where('tenant_id', $tenantId)->withoutTrashed(),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:50'],
            'country' => ['required', 'string', 'size:2'],
            'currency' => ['required', 'string', 'size:3'],
            'timezone' => ['required', 'timezone'],
            'invoice_prefix' => ['nullable', 'string', 'max:20'],
            'tax_enabled' => ['nullable', 'boolean'],
            'tax_name' => ['nullable', 'required_if:tax_enabled,1', 'string', 'max:100'],
            'tax_rate' => ['nullable', 'required_if:tax_enabled,1', 'numeric', 'min:0', 'max:100'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'is_main' => ['nullable', 'boolean'],
        ];
    }
}
