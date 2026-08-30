<?php

namespace App\Http\Requests\Payroll;

use App\Models\PayrollPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayrollPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PayrollPeriod::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => [$this->user()?->hasRole('Super Admin') ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'pay_date' => ['nullable', 'date', 'after_or_equal:end_date'],
            'status' => ['nullable', Rule::in([PayrollPeriod::STATUS_DRAFT, PayrollPeriod::STATUS_OPEN])],
        ];
    }
}
