<?php

namespace App\Http\Requests\Expense;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Expense::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => [$this->user()?->hasRole('Super Admin') ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
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
            'payment_method_id' => [Rule::requiredIf(fn () => (float) $this->input('paid_amount') > 0), 'nullable', 'integer', 'exists:payment_methods,id'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_reference_number' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['nullable', 'date'],
            'payment_notes' => ['nullable', 'string', 'max:3000'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:5120'],
        ];
    }
}
