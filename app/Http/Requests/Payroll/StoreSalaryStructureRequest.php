<?php

namespace App\Http\Requests\Payroll;

use App\Models\StaffSalaryStructure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalaryStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', StaffSalaryStructure::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => [$this->user()?->hasRole('Super Admin') ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'staff_id' => ['required', 'integer', 'exists:staff,id'],
            'salary_type' => ['required', Rule::in(StaffSalaryStructure::TYPES)],
            'basic_salary' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'daily_rate' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'overtime_enabled' => ['nullable', 'boolean'],
            'overtime_rate' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'commission_enabled' => ['nullable', 'boolean'],
            'payroll_frequency' => ['nullable', Rule::in(['monthly', 'weekly', 'biweekly'])],
            'payment_method' => ['nullable', 'string', 'max:80'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:255'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['nullable', Rule::in([StaffSalaryStructure::STATUS_ACTIVE, StaffSalaryStructure::STATUS_INACTIVE])],
        ];
    }
}
