<?php

namespace App\Http\Requests\Loyalty;

use App\Models\MembershipPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMembershipPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', MembershipPlan::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => [$this->user()?->hasRole('Super Admin') ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:3000'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_type' => ['required', Rule::in(['days', 'months', 'years'])],
            'duration_value' => ['required', 'integer', 'min:1'],
            'billing_type' => ['required', Rule::in(['one_time', 'recurring'])],
            'joining_fee' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in([MembershipPlan::STATUS_ACTIVE, MembershipPlan::STATUS_INACTIVE])],
            'is_featured' => ['nullable', 'boolean'],
            'benefits' => ['nullable', 'array'],
            'benefits.*.benefit_type' => ['required_with:benefits', 'string', 'max:80'],
            'benefits.*.discount_type' => ['nullable', Rule::in(['percentage', 'fixed'])],
            'benefits.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'benefits.*.service_id' => ['nullable', 'integer', 'exists:services,id'],
            'benefits.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'benefits.*.loyalty_multiplier' => ['nullable', 'numeric', 'min:1', 'max:10'],
            'benefits.*.priority' => ['nullable', 'integer', 'min:0'],
            'benefits.*.status' => ['nullable', 'string', 'max:50'],
        ];
    }
}
