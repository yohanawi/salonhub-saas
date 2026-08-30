<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpensePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pay', $this->route('expense')) ?? false;
    }

    public function rules(): array
    {
        return [
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
