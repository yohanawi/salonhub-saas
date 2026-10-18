<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

class StoreRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('refund', $this->route('invoice')) ?? false;
    }

    public function rules(): array
    {
        return [
            'payment_method_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:3000'],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'items' => ['nullable', 'array'],
            'items.*' => ['array'],
            'items.*.quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.restock' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'payment_method_id' => 'refund method',
            'amount' => 'refund amount',
        ];
    }
}
