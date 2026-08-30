<?php

namespace App\Http\Requests\Promotions;

use App\Models\Promotion;
use App\Models\PromotionCoupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePromotionCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PromotionCoupon::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => [$this->user()?->hasRole('Super Admin') ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
            'promotion_id' => ['required', 'integer'],
            'code' => ['required', 'string', 'max:100'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_customer_limit' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::in(PromotionCoupon::STATUSES)],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $tenantId = $this->tenantIdForValidation();

                if (! $tenantId) {
                    return;
                }

                $promotion = Promotion::withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->find($this->integer('promotion_id'));

                if (! $promotion) {
                    $validator->errors()->add('promotion_id', 'Select a promotion that belongs to this salon.');
                }

                $exists = PromotionCoupon::withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->whereRaw('UPPER(code) = ?', [strtoupper(trim((string) $this->input('code')))])
                    ->when($this->route('coupon'), fn ($query, $coupon) => $query->whereKeyNot($coupon->id))
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('code', 'This coupon code already exists for this salon.');
                }
            },
        ];
    }

    protected function tenantIdForValidation(): ?int
    {
        return $this->user()?->hasRole('Super Admin') ? $this->integer('tenant_id') : $this->user()?->tenant_id;
    }
}
