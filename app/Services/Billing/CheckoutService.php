<?php

namespace App\Services\Billing;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use App\Services\Appointment\AppointmentPricingService;
use App\Services\Inventory\InventoryService;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Membership\MembershipBenefitService;
use App\Services\Promotions\PromotionService;
use App\Services\Settings\SettingService;
use App\Services\StaffAvailabilityService;
use Illuminate\Support\Collection;
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
        private readonly PromotionService $promotions,
        private readonly TaxService $taxes,
        private readonly InvoiceNumberService $invoiceNumbers,
        private readonly AppointmentPricingService $pricing,
        private readonly StaffAvailabilityService $staffAvailability,
        private readonly TipService $tips,
        private readonly PackageService $packages,
        private readonly DepositService $deposits,
        private readonly SettingService $settings
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
                    'tax_rate' => 0,
                    'tax_amount' => 0,
                    'net_amount' => $net,
                    'total' => $net,
                    'total_amount' => $net,
                ];
            });

            $productSnapshots = $this->productSnapshots($appointment->branch, $data['product_items'] ?? []);

            return $this->createInvoice($appointment->branch, $appointment->customer, $appointment, $serviceSnapshots->merge($productSnapshots), $data, $user);
        });
    }

    /**
     * Walk-in and retail sales that are not tied to an appointment.
     */
    public function quickSale(Branch $branch, ?Customer $customer, array $data, User $user): Invoice
    {
        $branch->loadMissing('tenant');

        if ($customer && (int) $customer->tenant_id !== (int) $branch->tenant_id) {
            throw ValidationException::withMessages([
                'customer_id' => 'The selected customer does not belong to this salon.',
            ]);
        }

        $this->paymentMethods->ensureDefaults($branch->tenant);

        return DB::transaction(function () use ($branch, $customer, $data, $user) {
            $items = $this->serviceSnapshots($branch, $data['service_items'] ?? [])
                ->merge($this->productSnapshots($branch, $data['product_items'] ?? []));

            return $this->createInvoice($branch, $customer, null, $items, $data, $user);
        });
    }

    private function createInvoice(Branch $branch, ?Customer $customer, ?Appointment $appointment, Collection $items, array $data, User $user): Invoice
    {
        $branch->loadMissing('tenant');
        $tenant = $branch->tenant;

        $tipLines = $this->tips->linesFromInput($branch, $data['tips'] ?? []);
        $tipTotal = round((float) $tipLines->sum('amount'), 2);
        // Package credits go first, so membership and promotion discounts only apply to what is left to pay.
        $packageResult = $this->packages->applyToItemSnapshots($customer, $items, (bool) ($data['use_package_credits'] ?? true));
        $membershipResult = $this->membershipBenefits->applyToItemSnapshots($customer, $packageResult['items']);
        $promotionResult = $this->promotions->applyToItemSnapshots($tenant, $customer, $branch, $membershipResult['items'], $data);

        if ($promotionResult['items']->isEmpty()) {
            throw ValidationException::withMessages([
                $appointment ? 'appointment' : 'items' => $appointment ? 'This checkout does not have invoice items.' : 'Add at least one service or product to the sale.',
            ]);
        }

        $subtotal = $promotionResult['items']->sum('gross_amount');
        $lineDiscount = $promotionResult['items']->sum('discount_amount');
        $invoiceDiscount = min(max(0, (float) ($data['discount_amount'] ?? 0)), max(0, $subtotal - $lineDiscount));
        $this->ensureManualDiscountWithinLimit($branch, $subtotal, $invoiceDiscount);
        $discount = min($subtotal, $lineDiscount + $invoiceDiscount);

        $taxConfig = $this->taxes->configFor($branch);
        $taxResult = $this->taxes->applyToItemSnapshots($promotionResult['items'], $taxConfig, $invoiceDiscount);
        $itemSnapshots = $taxResult['items'];
        $tax = $taxResult['tax'];
        // Inclusive prices already contain the tax, so it is reported but not added on top.
        $total = max(0, round($subtotal - $discount + ($taxConfig['inclusive'] ? 0 : $tax), 2));
        $issuedAt = now();
        $account = $customer ? $this->loyalty->accountFor($customer) : null;

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'customer_id' => $customer?->id,
            'appointment_id' => $appointment?->id,
            'loyalty_account_id' => $account?->id,
            'customer_membership_id' => $membershipResult['membership']?->id,
            'promotion_id' => $promotionResult['promotion']?->id,
            'promotion_coupon_id' => $promotionResult['coupon']?->id,
            'invoice_number' => $this->invoiceNumbers->next($tenant->id, $branch, $issuedAt),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'membership_discount_amount' => $membershipResult['discount'],
            'package_discount_amount' => $packageResult['discount'],
            'promotion_name' => $promotionResult['promotion']?->name,
            'promotion_coupon_code' => $promotionResult['coupon']?->code,
            'promotion_discount_type' => $promotionResult['promotion']?->discount_type,
            'promotion_discount_value' => $promotionResult['promotion']?->discount_value ?? 0,
            'promotion_discount_amount' => $promotionResult['discount'],
            'loyalty_redemption_amount' => 0,
            'loyalty_points_redeemed' => 0,
            'loyalty_points_earned' => 0,
            'tax' => $tax,
            'tax_name' => $tax > 0 ? $taxConfig['name'] : null,
            'tax_inclusive' => $tax > 0 && $taxConfig['inclusive'],
            'total' => $total,
            'tip_amount' => $tipTotal,
            'paid_amount' => 0,
            'balance_amount' => round($total + $tipTotal, 2),
            'status' => Invoice::STATUS_ISSUED,
            'payment_status' => Invoice::PAYMENT_UNPAID,
            'issued_at' => $issuedAt,
            'notes' => $data['notes'] ?? null,
            'created_by' => $user->id,
            'cashier_staff_id' => $data['cashier_staff_id'] ?? null,
            'updated_by' => $user->id,
        ]);

        $itemSnapshots->each(fn (array $item) => $invoice->items()->create($item));
        $this->packages->recordUsage($invoice);
        $this->tips->recordForInvoice($invoice, $tipLines, $user);

        if ((int) ($data['loyalty_points_to_redeem'] ?? 0) > 0) {
            $this->loyalty->redeemForInvoice($invoice->fresh(['tenant', 'customer']), (int) $data['loyalty_points_to_redeem'], $user);
            $invoice->refresh();
        }

        if ($appointment) {
            $this->deposits->applyToInvoice($invoice, $appointment, $user, $data['cashier_staff_id'] ?? null);
        }

        $this->recordPayments($invoice, $data, $user);

        $invoice->load(['branch', 'items.product']);
        $this->membershipBenefits->recordInvoiceUsage($invoice, $user);
        $this->inventory->deductInvoiceProducts($invoice, $user);

        return $invoice->fresh(['branch', 'customer', 'appointment', 'cashier', 'items.staff', 'payments.paymentMethod', 'payments.receiver']);
    }

    /**
     * Membership, promotion and loyalty discounts are only known here, so the tender lines may add up
     * to more than is finally due; PaymentService::recordLines settles that.
     */
    private function recordPayments(Invoice $invoice, array $data, User $user): void
    {
        $lines = collect($data['payments'] ?? [])
            ->filter(fn ($line) => is_array($line))
            ->map(fn (array $line, int|string $index) => $line + ['index' => $index]);

        if ($lines->isEmpty() && filled($data['payment_method_id'] ?? null)) {
            $lines = collect([[
                'index' => 0,
                'payment_method_id' => $data['payment_method_id'],
                'amount' => $data['amount'] ?? $invoice->total,
                'cash_received' => $data['cash_received'] ?? null,
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'gateway_reference' => $data['gateway_reference'] ?? null,
            ]]);
        }

        $due = (float) Invoice::withoutTenantScope()->whereKey($invoice->id)->value('balance_amount');

        // Nothing to pay is fine when package credits or a deposit covered the bill; otherwise an
        // invoice may only be left unpaid when the salon allows credit sales.
        if ($lines->isEmpty() && $due > 0.009 && ! $this->settings->get('pos.allow_credit_sales', $invoice->tenant_id, $invoice->branch_id)) {
            throw ValidationException::withMessages([
                'payments' => 'Add a payment for the LKR ' . number_format($due, 2) . ' due.',
            ]);
        }

        // Paying only part of the bill (the rest left as balance due) can be switched off in Settings > Sales.
        if ($lines->isNotEmpty()
            && round((float) $lines->sum(fn ($line) => (float) $line['amount']), 2) < $due - 0.009
            && ! $this->settings->get('pos.allow_partial_payment', $invoice->tenant_id, $invoice->branch_id)) {
            throw ValidationException::withMessages([
                'payments' => 'This salon takes the full amount at checkout. Add payments for the LKR ' . number_format($due, 2) . ' due.',
            ]);
        }

        if ($lines->isEmpty()) {
            // A deposit that covered everything has already settled the invoice through its payment.
            if ($due <= 0.009 && $invoice->fresh()->payment_status !== Invoice::PAYMENT_PAID) {
                $this->payments->settleWithoutPayment($invoice, $user);
            }

            return;
        }

        $this->payments->recordLines($invoice, $lines->all(), $data['payment_notes'] ?? null, $user, $data['cashier_staff_id'] ?? null);
    }

    /**
     * Settings > Customers > Maximum Manual Discount caps the discount typed in at the till, as a share of the bill.
     */
    private function ensureManualDiscountWithinLimit(Branch $branch, float $subtotal, float $manualDiscount): void
    {
        $maximumPercent = (float) $this->settings->get('promotion.maximum_manual_discount_percent', $branch->tenant_id, $branch->id);

        if ($manualDiscount <= 0 || $maximumPercent >= 100) {
            return;
        }

        $maximum = round($subtotal * $maximumPercent / 100, 2);

        if ($manualDiscount > $maximum + 0.009) {
            throw ValidationException::withMessages([
                'discount_amount' => 'Manual discounts are limited to ' . rtrim(rtrim(number_format($maximumPercent, 2), '0'), '.') . '% of the bill (LKR ' . number_format($maximum, 2) . ').',
            ]);
        }
    }

    private function serviceSnapshots(Branch $branch, array $lines): Collection
    {
        return collect($lines)
            ->filter(fn (array $line) => filled($line['service_id'] ?? null))
            ->map(function (array $line, int|string $index) use ($branch) {
                $service = Service::withoutTenantScope()
                    ->with(['branches', 'staff'])
                    ->where('tenant_id', $branch->tenant_id)
                    ->where('is_active', true)
                    ->find($line['service_id']);

                if (! $service || ! $service->isAvailableAt($branch)) {
                    throw ValidationException::withMessages([
                        "service_items.{$index}.service_id" => 'Select a service that is available at ' . $branch->name . '.',
                    ]);
                }

                $staff = $this->staffForLine($branch, $line['staff_id'] ?? null, "service_items.{$index}.staff_id");

                if (! $staff) {
                    throw ValidationException::withMessages([
                        "service_items.{$index}.staff_id" => "Select who performed {$service->name}.",
                    ]);
                }

                if (! $this->staffAvailability->canPerformService($staff, $service)) {
                    throw ValidationException::withMessages([
                        "service_items.{$index}.staff_id" => "{$staff->full_name} is not assigned to {$service->name}.",
                    ]);
                }

                $quantity = max(1, (int) ($line['quantity'] ?? 1));
                $unitPrice = (float) $this->pricing->snapshot($service, $branch, $staff)['unit_price'];
                $gross = round($unitPrice * $quantity, 2);

                return [
                    'tenant_id' => $branch->tenant_id,
                    'item_type' => 'service',
                    'item_id' => $service->id,
                    'service_id' => $service->id,
                    'service_category_id' => $service->category_id,
                    'product_id' => null,
                    'staff_id' => $staff->id,
                    'description' => $service->name,
                    'item_name' => $service->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'gross_amount' => $gross,
                    'discount' => 0,
                    'discount_amount' => 0,
                    'tax_rate' => 0,
                    'tax_amount' => 0,
                    'net_amount' => $gross,
                    'total' => $gross,
                    'total_amount' => $gross,
                ];
            })
            ->values();
    }

    private function productSnapshots(Branch $branch, array $lines): Collection
    {
        $lines = collect($lines)->filter(fn (array $item) => filled($item['product_id'] ?? null) && (int) ($item['quantity'] ?? 0) > 0);
        $requested = [];

        $snapshots = $lines->map(function (array $item, int|string $index) use ($branch, &$requested) {
            $product = Product::withoutTenantScope()
                ->where('tenant_id', $branch->tenant_id)
                ->where('is_active', true)
                ->where('is_sellable', true)
                ->findOrFail($item['product_id']);

            $quantity = (int) $item['quantity'];
            $requested[$product->id] = ($requested[$product->id] ?? 0) + $quantity;
            $this->ensureStock($branch, $product, $requested[$product->id], "product_items.{$index}.quantity");

            $staff = $this->staffForLine($branch, $item['staff_id'] ?? null, "product_items.{$index}.staff_id");
            $unitPrice = $product->priceAt($branch);
            $gross = $unitPrice * $quantity;

            // Tax is applied after membership and promotion discounts, by TaxService.
            return [
                'tenant_id' => $branch->tenant_id,
                'item_type' => 'product',
                'item_id' => $product->id,
                'service_id' => null,
                'service_category_id' => null,
                'product_id' => $product->id,
                'staff_id' => $staff?->id,
                'description' => $product->sku,
                'item_name' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'gross_amount' => $gross,
                'discount' => 0,
                'discount_amount' => 0,
                'tax_rate' => (float) $product->tax_rate,
                'tax_amount' => 0,
                'net_amount' => $gross,
                'total' => $gross,
                'total_amount' => $gross,
            ];
        });

        return $snapshots->values();
    }

    /**
     * Fails before anything is written, with an error on the offending line, instead of the
     * generic stock error InventoryService raises once the invoice already exists.
     */
    private function ensureStock(Branch $branch, Product $product, int $quantity, string $field): void
    {
        if (! $product->track_inventory || $product->allow_negative_stock) {
            return;
        }

        $onHand = (int) Inventory::withoutTenantScope()
            ->where('tenant_id', $branch->tenant_id)
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->value('quantity_on_hand');

        if ($quantity > $onHand) {
            throw ValidationException::withMessages([
                $field => "Only {$onHand} units of {$product->name} are available at {$branch->name}.",
            ]);
        }
    }

    private function staffForLine(Branch $branch, mixed $staffId, string $field): ?Staff
    {
        if (blank($staffId)) {
            return null;
        }

        $staff = Staff::withoutTenantScope()
            ->where('tenant_id', $branch->tenant_id)
            ->where('status', Staff::STATUS_ACTIVE)
            ->find($staffId);

        if (! $staff || ! $this->staffAvailability->canWorkAtBranch($staff, $branch)) {
            throw ValidationException::withMessages([
                $field => 'Select an active staff member who works at ' . $branch->name . '.',
            ]);
        }

        return $staff;
    }
}
