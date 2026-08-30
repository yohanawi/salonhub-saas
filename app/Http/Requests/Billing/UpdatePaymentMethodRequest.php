<?php

namespace App\Http\Requests\Billing;

use App\Models\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        $paymentMethod = $this->route('payment_method');

        return $paymentMethod instanceof PaymentMethod
            && ($this->user()?->can('update', $paymentMethod) ?? false);
    }

    public function rules(): array
    {
        $paymentMethod = $this->route('payment_method');

        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => [
                'required',
                'string',
                'max:80',
                Rule::unique('payment_methods', 'code')
                    ->where('tenant_id', $paymentMethod->tenant_id)
                    ->ignore($paymentMethod->id),
            ],
            'type' => ['required', Rule::in(PaymentMethod::TYPES)],
            'is_active' => ['nullable', 'boolean'],
            'requires_reference' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
