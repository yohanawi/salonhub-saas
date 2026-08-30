<?php

namespace App\Http\Requests\Billing;

use App\Models\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PaymentMethod::class) ?? false;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->hasRole('Super Admin') ? $this->integer('tenant_id') : $this->user()->tenant_id;

        return [
            'tenant_id' => [$this->user()->hasRole('Super Admin') ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:100'],
            'code' => [
                'required',
                'string',
                'max:80',
                Rule::unique('payment_methods', 'code')->where('tenant_id', $tenantId),
            ],
            'type' => ['required', Rule::in(PaymentMethod::TYPES)],
            'is_active' => ['nullable', 'boolean'],
            'requires_reference' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
