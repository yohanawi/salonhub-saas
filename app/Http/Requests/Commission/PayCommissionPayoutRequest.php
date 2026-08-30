<?php

namespace App\Http\Requests\Commission;

use Illuminate\Foundation\Http\FormRequest;

class PayCommissionPayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pay', $this->route('payout')) ?? false;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', 'max:80'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
