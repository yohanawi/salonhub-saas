<?php

namespace App\Http\Requests\Billing\Concerns;

use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Promotion;
use App\Services\Settings\SettingService;
use Illuminate\Validation\Validator;

/**
 * Payment, discount, product and promotion rules shared by appointment checkout and quick sale.
 * Payments arrive as `payments[]` lines so a bill can be split across methods; the older single
 * `payment_method_id` / `amount` fields are still accepted as one line.
 */
trait ValidatesSalePayment
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('payments') && $this->has('payment_method_id')) {
            $this->merge([
                'payments' => [[
                    'payment_method_id' => $this->input('payment_method_id'),
                    'amount' => $this->input('amount'),
                    'cash_received' => $this->input('cash_received'),
                    'transaction_reference' => $this->input('transaction_reference'),
                    'gateway_reference' => $this->input('gateway_reference'),
                ]],
            ]);
        }
    }

    protected function salePaymentRules(): array
    {
        return [
            'payments' => ['required', 'array', 'min:1', 'max:10'],
            'payments.*' => ['array'],
            'payments.*.payment_method_id' => ['required', 'integer'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payments.*.cash_received' => ['nullable', 'numeric', 'min:0'],
            'payments.*.transaction_reference' => ['nullable', 'string', 'max:255'],
            'payments.*.gateway_reference' => ['nullable', 'string', 'max:255'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'loyalty_points_to_redeem' => ['nullable', 'integer', 'min:0'],
            'promotion_id' => ['nullable', 'integer'],
            'coupon_code' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'payment_notes' => ['nullable', 'string', 'max:3000'],
            'product_items' => ['nullable', 'array'],
            'product_items.*' => ['array'],
            'product_items.*.product_id' => ['nullable', 'integer'],
            'product_items.*.quantity' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'payments.*.payment_method_id' => 'payment method',
            'payments.*.amount' => 'payment amount',
            'payments.*.cash_received' => 'cash received',
        ] + $this->extraAttributes();
    }

    protected function extraAttributes(): array
    {
        return [];
    }

    protected function validateSalePayment(Validator $validator, int $tenantId, ?int $branchId): void
    {
        $settings = app(SettingService::class);

        if ((float) $this->input('discount_amount', 0) > 0) {
            if (! $settings->get('pos.allow_manual_discount', $tenantId, $branchId)) {
                $validator->errors()->add('discount_amount', 'Manual discounts are disabled for this salon.');
            } elseif (! $this->user()->can('applyDiscount', Invoice::class)) {
                $validator->errors()->add('discount_amount', 'You do not have permission to apply a manual discount.');
            }
        }

        $lines = collect((array) $this->input('payments', []))->filter(fn ($line) => is_array($line));

        if ($lines->count() > 1 && ! $settings->get('pos.allow_split_payment', $tenantId, $branchId)) {
            $validator->errors()->add('payments', 'Split payments are disabled for this salon. Use a single payment method.');
        }

        foreach ($lines as $index => $line) {
            $method = PaymentMethod::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->find($line['payment_method_id'] ?? null);

            if (! $method || ! $method->is_active) {
                $validator->errors()->add("payments.{$index}.payment_method_id", 'Select an active payment method for this salon.');
                continue;
            }

            if ($method->requires_reference && blank($line['transaction_reference'] ?? null)) {
                $validator->errors()->add("payments.{$index}.transaction_reference", "A reference is required for {$method->name}.");
            }

            if ($method->type === PaymentMethod::TYPE_CASH && filled($line['cash_received'] ?? null) && (float) $line['cash_received'] < (float) ($line['amount'] ?? 0)) {
                $validator->errors()->add("payments.{$index}.cash_received", 'Cash received cannot be less than the cash amount.');
            }
        }

        foreach ((array) $this->input('product_items', []) as $index => $item) {
            if (! is_array($item) || blank($item['product_id'] ?? null)) {
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
            $promotion = Promotion::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->find($this->integer('promotion_id'));

            if (! $promotion) {
                $validator->errors()->add('promotion_id', 'Select a promotion that belongs to this salon.');
            }
        }
    }
}
