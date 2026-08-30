<?php

namespace App\Http\Requests\Promotions;

class UpdatePromotionCouponRequest extends StorePromotionCouponRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('coupon')) ?? false;
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['tenant_id'] = ['nullable', 'integer', 'exists:tenants,id'];

        return $rules;
    }

    protected function tenantIdForValidation(): ?int
    {
        return $this->route('coupon')?->tenant_id;
    }
}
