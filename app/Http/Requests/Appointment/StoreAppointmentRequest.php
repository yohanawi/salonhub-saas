<?php

namespace App\Http\Requests\Appointment;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Services\BranchContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Appointment::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => [$this->user()->hasRole('Super Admin') ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
            'branch_id' => ['required', 'integer'],
            'customer_id' => ['required', 'integer'],
            'booking_source' => ['required', Rule::in(Appointment::BOOKING_SOURCES)],
            'status' => ['nullable', Rule::in([Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED])],
            'starts_at' => ['required', 'date'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'customer_notes' => ['nullable', 'string', 'max:3000'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
            'services' => ['required', 'array', 'min:1'],
            'services.*.service_id' => ['required', 'integer'],
            'services.*.staff_id' => ['required', 'integer'],
            'services.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
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

                $branch = Branch::withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->whereKey($this->integer('branch_id'))
                    ->first();

                if (! $branch) {
                    $validator->errors()->add('branch_id', 'The selected branch does not belong to this salon.');
                } elseif (! $this->user()->hasRole('Super Admin') && ! app(BranchContext::class)->canAccess($this->user(), $branch)) {
                    $validator->errors()->add('branch_id', 'You cannot create appointments for this branch.');
                }

                if (! Customer::withoutTenantScope()->where('tenant_id', $tenantId)->whereKey($this->integer('customer_id'))->exists()) {
                    $validator->errors()->add('customer_id', 'The selected customer does not belong to this salon.');
                }

                foreach (array_values($this->input('services', [])) as $index => $line) {
                    if (! empty($line['service_id']) && ! Service::withoutTenantScope()->where('tenant_id', $tenantId)->whereKey($line['service_id'])->exists()) {
                        $validator->errors()->add("services.{$index}.service_id", 'The selected service does not belong to this salon.');
                    }

                    if (! empty($line['staff_id']) && ! Staff::withoutTenantScope()->where('tenant_id', $tenantId)->whereKey($line['staff_id'])->exists()) {
                        $validator->errors()->add("services.{$index}.staff_id", 'The selected staff member does not belong to this salon.');
                    }
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
