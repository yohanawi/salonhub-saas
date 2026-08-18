<?php

namespace App\Http\Requests\Billing;

use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('checkout', Invoice::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'payment_method_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'loyalty_points_to_redeem' => ['nullable', 'integer', 'min:0'],
            'promotion_id' => ['nullable', 'integer'],
            'coupon_code' => ['nullable', 'string', 'max:100'],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'gateway_reference' => ['nullable', 'string', 'max:255'],
            'cash_received' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'payment_notes' => ['nullable', 'string', 'max:3000'],
            'product_items' => ['nullable', 'array'],
            'product_items.*.product_id' => ['nullable', 'integer'],
            'product_items.*.quantity' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $tenantId = $this->user()->hasRole('Super Admin')
                    ? $this->route('appointment')?->tenant_id
                    : $this->user()->tenant_id;

                if (! $tenantId) {
                    return;
                }

                $method = PaymentMethod::withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->find($this->integer('payment_method_id'));

                if (! $method || ! $method->is_active) {
                    $validator->errors()->add('payment_method_id', 'Select an active payment method for this salon.');
                    return;
                }

                if ($method->requires_reference && blank($this->input('transaction_reference'))) {
                    $validator->errors()->add('transaction_reference', 'A reference is required for this payment method.');
                }

                if ($method->type === PaymentMethod::TYPE_CASH && $this->filled('cash_received') && (float) $this->input('cash_received') < (float) $this->input('amount')) {
                    $validator->errors()->add('cash_received', 'Cash received cannot be less than the payment amount.');
                }

                foreach ($this->input('product_items', []) as $index => $item) {
                    if (blank($item['product_id'] ?? null)) {
                        continue;
                    }

                    $product = Product::withoutTenantScope()
                        ->where('tenant_id', $tenantId)
                        ->where('is_active', true)
                        ->where('is_sellable', true)
                        ->find($item['product_id']);

                    if (! $product) {
                        $validator->errors()->add("product_items.{$index}.product_id", 'Select an active sellable product for this salon.');
                    }
                }

                if ($this->filled('promotion_id')) {
                    $promotion = \App\Models\Promotion::withoutTenantScope()
                        ->where('tenant_id', $tenantId)
                        ->find($this->integer('promotion_id'));

                    if (! $promotion) {
                        $validator->errors()->add('promotion_id', 'Select a promotion that belongs to this salon.');
                    }
                }
            },
        ];
    }
}
