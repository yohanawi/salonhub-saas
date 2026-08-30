<?php

namespace App\Http\Requests\CustomerManagement;

class UpdateCustomerRequest extends StoreCustomerRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('customer')) ?? false;
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['tenant_id'] = ['nullable'];

        return $rules;
    }

    protected function tenantId(): ?int
    {
        return $this->route('customer')?->tenant_id;
    }
}
