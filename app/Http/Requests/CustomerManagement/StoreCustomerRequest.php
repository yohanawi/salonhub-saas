<?php

namespace App\Http\Requests\CustomerManagement;

use App\Models\Branch;
use App\Models\Customer;
use App\Services\Customers\CustomerDuplicateService;
use App\Services\Settings\SettingService;
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
        // Settings > Customers decides whether phone and email are required.
        $settings = app(SettingService::class);
        $requirePhone = ! $tenantId || (bool) $settings->get('customer.require_phone', $tenantId);
        $requireEmail = $tenantId && (bool) $settings->get('customer.require_email', $tenantId);

        return [
            'tenant_id' => [$this->user()->hasRole('Super Admin') ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
            'branch_id' => ['nullable', 'integer'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => [$requireEmail ? 'required' : 'nullable', 'email', 'max:255'],
            'phone' => [$requirePhone ? 'required' : 'nullable', 'string', 'max:30'],
            'gender' => ['nullable', Rule::in(Customer::GENDERS)],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'anniversary_date' => ['nullable', 'date', 'before_or_equal:today'],
            'allergies' => ['nullable', 'string', 'max:2000'],
            'referral_code_used' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'note' => ['nullable', 'string', 'max:3000'],
            'marketing_consent' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(Customer::STATUSES)],
            // Ticked when staff confirm a matching phone/email belongs to a different person (e.g. a family phone).
            'allow_duplicate' => ['nullable', 'boolean'],
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

                if (! $this->boolean('allow_duplicate') && ! $validator->errors()->hasAny(['phone', 'email'])) {
                    $this->checkDuplicates($validator, $tenantId);
                }
            },
        ];
    }

    private function checkDuplicates(Validator $validator, int $tenantId): void
    {
        $duplicates = app(CustomerDuplicateService::class);
        $existing = $duplicates->find($tenantId, $this->input('phone'), $this->input('email'), $this->route('customer')?->id)->first();

        if (! $existing) {
            return;
        }

        foreach ($duplicates->describe($existing, $this->input('phone'), $this->input('email')) as $field => $label) {
            $validator->errors()->add($field, "{$existing->full_name} ({$existing->customer_code}) already has this {$label}.");
        }

        // Lets the form link to the existing record and offer "save anyway".
        session()->flash('duplicate_customer_id', $existing->id);
    }

    protected function tenantId(): ?int
    {
        return $this->user()->hasRole('Super Admin')
            ? ($this->filled('tenant_id') ? $this->integer('tenant_id') : null)
            : $this->user()->tenant_id;
    }
}
