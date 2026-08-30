<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('expense')) ?? false;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'expense_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:3000'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'subtotal' => ['required', 'numeric', 'min:0.01'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
