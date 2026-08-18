<?php

namespace App\Http\Requests\Commission;

use App\Models\CommissionSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCommissionSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('setting')) ?? false;
    }

    public function rules(): array
    {
        return [
            'commission_enabled' => ['nullable', 'boolean'],
            'default_service_commission_type' => ['required', Rule::in(CommissionSetting::TYPES)],
            'default_service_commission_value' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'default_product_commission_type' => ['required', Rule::in(CommissionSetting::TYPES)],
            'default_product_commission_value' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'calculation_basis' => ['required', Rule::in(CommissionSetting::BASES)],
            'earn_trigger' => ['required', Rule::in(CommissionSetting::TRIGGERS)],
            'requires_approval' => ['nullable', 'boolean'],
            'allow_manual_adjustment' => ['nullable', 'boolean'],
            'allow_negative_commission' => ['nullable', 'boolean'],
            'refund_behavior' => ['required', Rule::in(['reverse'])],
        ];
    }
}
