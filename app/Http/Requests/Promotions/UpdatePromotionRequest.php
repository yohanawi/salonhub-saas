<?php

namespace App\Http\Requests\Promotions;

class UpdatePromotionRequest extends StorePromotionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('promotion')) ?? false;
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['tenant_id'] = ['nullable', 'integer', 'exists:tenants,id'];

        return $rules;
    }

    protected function tenantIdForValidation(): ?int
    {
        return $this->route('promotion')?->tenant_id;
    }
}
