<?php

namespace App\Services\Billing;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Membership\MembershipBenefitService;
use App\Services\Promotions\PromotionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly PaymentMethodService $paymentMethods,
        private readonly InventoryService $inventory,
        private readonly LoyaltyService $loyalty,
        private readonly MembershipBenefitService $membershipBenefits,
        private readonly PromotionService $promotions
    ) {
    }

    public function checkoutAppointment(Appointment $appointment, array $data, User $user): Invoice
    {
        $appointment->loadMissing(['tenant', 'branch', 'customer', 'appointmentServices.service.category', 'appointmentServices.staff']);

        if ($appointment->status !== Appointment::STATUS_COMPLETED) {
            throw ValidationException::withMessages([
                'appointment' => 'Only completed appointments can be checked out.',
            ]);
        }

        if (Invoice::withoutTenantScope()->where('tenant_id', $appointment->tenant_id)->where('appointment_id', $appointment->id)->exists()) {
            throw ValidationException::withMessages([
                'appointment' => 'This appointment already has an invoice.',
            ]);
        }

        $this->paymentMethods->ensureDefaults($appointment->tenant);

        return DB::transaction(function () use ($appointment, $data, $user) {
            $serviceSnapshots = $appointment->appointmentServices->map(function ($appointmentService) {
                $unitPrice = (float) ($appointmentService->unit_price ?? $appointmentService->price);
                $discount = (float) $appointmentService->discount_amount;
                $gross = $unitPrice;
                $net = max(0, $gross - $discount);

                return [
                    'tenant_id' => $appointmentService->tenant_id,
                    'item_type' => 'service',
                    'item_id' => $appointmentService->service_id,
                    'service_id' => $appointmentService->service_id,
                    'service_category_id' => $appointmentService->service?->category_id,
                    'product_id' => null,
                    'staff_id' => $appointmentService->staff_id,
                    'description' => $appointmentService->service_name ?: $appointmentService->service?->name,
                    'item_name' => $appointmentService->service_name ?: $appointmentService->service?->name,
                    'quantity' => 1,
                    'unit_price' => $unitPrice,
                    'gross_amount' => $gross,
                    'discount' => $discount,
                    'discount_amount' => $discount,
                    'tax_amount' => 0,
                    'net_amount' => $net,
                    'total' => $net,
                    'total_amount' => $net,
                ];
            });

            $productSnapshots = collect($data['product_items'] ?? [])
                ->filter(fn (array $item) => filled($item['product_id'] ?? null) && (int) ($item['quantity'] ?? 0) > 0)
                ->map(function (array $item) use ($appointment) {
                    $product = Product::withoutTenantScope()
                        ->where('tenant_id', $appointment->tenant_id)
                        ->where('is_active', true)
                        ->where('is_sellable', true)
                        ->findOrFail($item['product_id']);

                    $quantity = (int) $item['quantity'];
                    $unitPrice = (float) $product->selling_price;
                    $tax = round(($unitPrice * $quantity) * ((float) $product->tax_rate / 100), 2);
                    $gross = $unitPrice * $quantity;
                    $net = $gross + $tax;

                    return [
                        'tenant_id' => $appointment->tenant_id,
                        'item_type' => 'product',
                        'item_id' => $product->id,
                    'service_id' => null,
                    'service_category_id' => null,
                    'product_id' => $product->id,
                        'staff_id' => null,
                        'description' => $product->sku,
                        'item_name' => $product->name,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'gross_amount' => $gross,
                        'discount' => 0,
                        'discount_amount' => 0,
                        'tax_amount' => $tax,
                        'net_amount' => $net,
                        'total' => $net,
                        'total_amount' => $net,
                    ];
                });

            $membershipResult = $this->membershipBenefits->applyToItemSnapshots($appointment->customer, $serviceSnapshots->merge($productSnapshots));
            $promotionResult = $this->promotions->applyToItemSnapshots($appointment->tenant, $appointment->customer, $appointment->branch, $membershipResult['items'], $data);
            $itemSnapshots = $promotionResult['items'];

            if ($itemSnapshots->isEmpty()) {
                throw ValidationException::withMessages([
                    'appointment' => 'This checkout does not have invoice items.',
                ]);
            }

            $subtotal = $itemSnapshots->sum('gross_amount');
            $lineDiscount = $itemSnapshots->sum('discount_amount');
            $invoiceDiscount = min(max(0, (float) ($data['discount_amount'] ?? 0)), max(0, $subtotal - $lineDiscount));
            $tax = max(0, (float) ($data['tax_amount'] ?? 0));
            $discount = min($subtotal, $lineDiscount + $invoiceDiscount);
            $total = max(0, $subtotal - $discount + $tax);
            $account = $appointment->customer ? $this->loyalty->accountFor($appointment->customer) : null;

            $invoice = Invoice::create([
                'tenant_id' => $appointment->tenant_id,
                'branch_id' => $appointment->branch_id,
                'customer_id' => $appointment->customer_id,
                'appointment_id' => $appointment->id,
                'loyalty_account_id' => $account?->id,
                'customer_membership_id' => $membershipResult['membership']?->id,
                'promotion_id' => $promotionResult['promotion']?->id,
                'promotion_coupon_id' => $promotionResult['coupon']?->id,
                'invoice_number' => 'INV-TMP-' . uniqid(),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'membership_discount_amount' => $membershipResult['discount'],
                'promotion_name' => $promotionResult['promotion']?->name,
                'promotion_coupon_code' => $promotionResult['coupon']?->code,
                'promotion_discount_type' => $promotionResult['promotion']?->discount_type,
                'promotion_discount_value' => $promotionResult['promotion']?->discount_value ?? 0,
                'promotion_discount_amount' => $promotionResult['discount'],
                'loyalty_redemption_amount' => 0,
                'loyalty_points_redeemed' => 0,
                'loyalty_points_earned' => 0,
                'tax' => $tax,
                'total' => $total,
                'paid_amount' => 0,
                'balance_amount' => $total,
                'status' => Invoice::STATUS_ISSUED,
                'payment_status' => Invoice::PAYMENT_UNPAID,
                'issued_at' => now(),
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $invoice->update([
                'invoice_number' => $this->invoiceNumber($invoice),
            ]);

            $itemSnapshots->each(fn (array $item) => $invoice->items()->create($item));

            if ((int) ($data['loyalty_points_to_redeem'] ?? 0) > 0) {
                $this->loyalty->redeemForInvoice($invoice->fresh(['tenant', 'customer']), (int) $data['loyalty_points_to_redeem'], $user);
                $invoice->refresh();
            }

            $method = PaymentMethod::withoutTenantScope()
                ->where('tenant_id', $invoice->tenant_id)
                ->findOrFail($data['payment_method_id']);

            $this->payments->record($invoice, $method, [
                'amount' => min((float) ($data['amount'] ?? $invoice->total), (float) $invoice->balance_amount),
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'gateway_reference' => $data['gateway_reference'] ?? null,
                'cash_received' => $data['cash_received'] ?? null,
                'notes' => $data['payment_notes'] ?? null,
            ], $user);

            $invoice->load(['branch', 'items.product']);
            $this->membershipBenefits->recordInvoiceUsage($invoice, $user);
            $this->inventory->deductInvoiceProducts($invoice, $user);

            return $invoice->fresh(['branch', 'customer', 'appointment', 'items.staff', 'payments.paymentMethod', 'payments.receiver']);
        });
    }

    private function invoiceNumber(Invoice $invoice): string
    {
        return 'INV-' . $invoice->issued_at->format('Y') . '-' . str_pad((string) $invoice->id, 6, '0', STR_PAD_LEFT);
    }
}
