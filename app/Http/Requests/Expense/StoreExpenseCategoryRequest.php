<?php

namespace App\Http\Requests\Expense;

use App\Models\ExpenseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ExpenseCategory::class) ?? false;
    }

    public function rules(): array
    {
        $tenantId = $this->user()?->hasRole('Super Admin') ? $this->input('tenant_id') : $this->user()?->tenant_id;

        return [
            'tenant_id' => [$this->user()?->hasRole('Super Admin') ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
            'parent_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255', Rule::unique('expense_categories', 'name')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'code' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:3000'],
            'color' => ['nullable', 'string', 'max:30'],
            'icon' => ['nullable', 'string', 'max:80'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
