<?php

namespace App\Http\Requests\Commission;

use App\Models\CommissionRule;
use App\Models\CommissionSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommissionRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CommissionRule::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => [$this->user()?->hasRole('Super Admin') ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'staff_id' => ['nullable', 'integer', 'exists:staff,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'commission_scope' => ['required', Rule::in(CommissionRule::SCOPES)],
            'commission_type' => ['required', Rule::in(CommissionSetting::TYPES)],
            'commission_value' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'calculate_on' => ['required', Rule::in(CommissionSetting::BASES)],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
