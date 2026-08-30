<?php

namespace App\Http\Requests\Promotions;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\MembershipPlan;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Promotion::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => [$this->user()?->hasRole('Super Admin') ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
            'promotion_type' => ['nullable', 'string', 'max:100'],
            'discount_type' => ['required', Rule::in(Promotion::DISCOUNT_TYPES)],
            'discount_value' => ['required', 'numeric', 'gt:0'],
            'maximum_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'application_type' => ['required', Rule::in(Promotion::APPLICATION_TYPES)],
            'minimum_spend' => ['nullable', 'numeric', 'min:0'],
            'minimum_quantity' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_customer_limit' => ['nullable', 'integer', 'min:1'],
            'customer_scope' => ['required', Rule::in(Promotion::CUSTOMER_SCOPES)],
            'branch_scope' => ['required', Rule::in(Promotion::BRANCH_SCOPES)],
            'target_scope' => ['required', Rule::in(Promotion::TARGET_SCOPES)],
            'is_stackable' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'status' => ['required', Rule::in(Promotion::STATUSES)],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['integer'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer'],
            'customer_ids' => ['nullable', 'array'],
            'customer_ids.*' => ['integer'],
            'membership_plan_ids' => ['nullable', 'array'],
            'membership_plan_ids.*' => ['integer'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->input('discount_type') === Promotion::DISCOUNT_PERCENTAGE && (float) $this->input('discount_value') > 100) {
                    $validator->errors()->add('discount_value', 'Percentage discounts cannot exceed 100%.');
                }

                $tenantId = $this->tenantIdForValidation();

                if (! $tenantId) {
                    return;
                }

                $this->validateTenantIds($validator, Branch::class, 'branch_ids', $tenantId, 'branch');
                $this->validateTenantIds($validator, Service::class, 'service_ids', $tenantId, 'service');
                $this->validateTenantIds($validator, Product::class, 'product_ids', $tenantId, 'product');
                $this->validateTenantIds($validator, Customer::class, 'customer_ids', $tenantId, 'customer');
                $this->validateTenantIds($validator, MembershipPlan::class, 'membership_plan_ids', $tenantId, 'membership plan');

                if ($this->input('branch_scope') === Promotion::BRANCH_SELECTED && empty($this->input('branch_ids', []))) {
                    $validator->errors()->add('branch_ids', 'Select at least one branch.');
                }

                if ($this->input('target_scope') === Promotion::TARGET_SERVICES && empty($this->input('service_ids', []))) {
                    $validator->errors()->add('service_ids', 'Select at least one service.');
                }

                if ($this->input('target_scope') === Promotion::TARGET_PRODUCTS && empty($this->input('product_ids', []))) {
                    $validator->errors()->add('product_ids', 'Select at least one product.');
                }

                if ($this->input('customer_scope') === Promotion::CUSTOMER_SELECTED && empty($this->input('customer_ids', []))) {
                    $validator->errors()->add('customer_ids', 'Select at least one customer.');
                }
            },
        ];
    }

    protected function tenantIdForValidation(): ?int
    {
        return $this->user()?->hasRole('Super Admin') ? $this->integer('tenant_id') : $this->user()?->tenant_id;
    }

    private function validateTenantIds(Validator $validator, string $model, string $field, int $tenantId, string $label): void
    {
        $ids = collect($this->input($field, []))->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return;
        }

        $count = $model::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->count();

        if ($count !== $ids->count()) {
            $validator->errors()->add($field, "One or more selected {$label} records do not belong to this salon.");
        }
    }
}
