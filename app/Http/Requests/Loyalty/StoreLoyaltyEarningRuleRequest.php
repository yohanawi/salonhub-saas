<?php

namespace App\Http\Requests\Loyalty;

use App\Models\LoyaltyEarningRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoyaltyEarningRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', LoyaltyEarningRule::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => [$this->user()?->hasRole('Super Admin') ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
            'loyalty_program_id' => ['required', 'integer', 'exists:loyalty_programs,id'],
            'name' => ['required', 'string', 'max:255'],
            'rule_type' => ['required', Rule::in([LoyaltyEarningRule::TYPE_SPEND, LoyaltyEarningRule::TYPE_BONUS])],
            'spend_amount' => ['nullable', 'numeric', 'min:0.01'],
            'points_awarded' => ['required', 'integer', 'min:1'],
            'minimum_purchase_amount' => ['nullable', 'numeric', 'min:0'],
            'maximum_points_per_transaction' => ['nullable', 'integer', 'min:1'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'service_category_id' => ['nullable', 'integer', 'exists:service_categories,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in([LoyaltyEarningRule::STATUS_ACTIVE, LoyaltyEarningRule::STATUS_INACTIVE])],
        ];
    }
}
