<?php

namespace App\Http\Requests\Loyalty;

use App\Models\LoyaltyProgram;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoyaltyProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', LoyaltyProgram::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => [$this->user()?->hasRole('Super Admin') ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'status' => ['required', Rule::in([LoyaltyProgram::STATUS_ACTIVE, LoyaltyProgram::STATUS_INACTIVE])],
            'points_expiry_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'minimum_redeem_points' => ['nullable', 'integer', 'min:0'],
            'maximum_redeem_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'redemption_points' => ['required', 'integer', 'min:1'],
            'redemption_value' => ['required', 'numeric', 'min:0.01'],
            'allow_partial_redemption' => ['nullable', 'boolean'],
            'allow_points_on_discounted_sales' => ['nullable', 'boolean'],
        ];
    }
}
