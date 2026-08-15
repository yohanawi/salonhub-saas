<?php

namespace App\Http\Requests\CustomerManagement;

use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Customer::class) ?? false;
    }

    public function rules(): array
    {
        $tenantId = $this->tenantId();

        return [
            'tenant_id' => [$this->user()->hasRole('Super Admin') ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
            'branch_id' => ['nullable', 'integer'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'gender' => ['nullable', Rule::in(Customer::GENDERS)],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'note' => ['nullable', 'string', 'max:3000'],
            'marketing_consent' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(Customer::STATUSES)],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $tenantId = $this->tenantId();

                if (! $tenantId) {
                    return;
                }

                if ($this->filled('branch_id') && ! Branch::withoutTenantScope()->where('tenant_id', $tenantId)->whereKey($this->integer('branch_id'))->exists()) {
                    $validator->errors()->add('branch_id', 'The selected branch does not belong to this salon.');
                }
            },
        ];
    }

    protected function tenantId(): ?int
    {
        return $this->user()->hasRole('Super Admin')
            ? ($this->filled('tenant_id') ? $this->integer('tenant_id') : null)
            : $this->user()->tenant_id;
    }
}
