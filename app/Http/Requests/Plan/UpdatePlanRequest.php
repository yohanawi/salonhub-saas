<?php

namespace App\Http\Requests\Plan;

use App\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Super Admin') ?? false;
    }

    public function rules(): array
    {
        $plan = $this->route('plan');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('plans', 'slug')->ignore($plan)],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'billing_period' => ['required', Rule::in(array_keys(Plan::BILLING_PERIODS))],
            'max_branches' => ['nullable', 'integer', 'min:1'],
            'max_staff' => ['nullable', 'integer', 'min:1'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'max_customers' => ['nullable', 'integer', 'min:1'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', Rule::in(array_keys(Plan::FEATURE_OPTIONS))],
            'is_active' => ['nullable', 'boolean'],
            'is_recommended' => ['nullable', 'boolean'],
        ];
    }
}
