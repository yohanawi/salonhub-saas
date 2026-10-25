<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\Commission\CommissionGenerator;
use App\Services\Customers\ReferralService;
use App\Services\Loyalty\CustomerRewardsService;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Settings\SettingService;
use App\Services\Promotions\PromotionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function record(Invoice $invoice, PaymentMethod $method, array $data, User $user): Payment
    {
        if ((int) $method->tenant_id !== (int) $invoice->tenant_id || ! $method->is_active) {
            throw ValidationException::withMessages([
                'payment_method_id' => 'The selected payment method is not available for this salon.',
            ]);
        }

        if ($method->requires_reference && blank($data['transaction_reference'] ?? null)) {
            throw ValidationException::withMessages([
                'transaction_reference' => 'A transaction reference is required for this payment method.',
            ]);
        }

        if ($method->type === PaymentMethod::TYPE_DEPOSIT && blank($data['appointment_deposit_id'] ?? null)) {
            throw ValidationException::withMessages([
                $data['error_field'] ?? 'payment_method_id' => 'Deposits are applied automatically at checkout.',
            ]);
        }

        $giftCardField = $data['gift_card_field'] ?? 'gift_card_code';

        if ($method->type === PaymentMethod::TYPE_GIFT_CARD) {
            if (blank($data['gift_card_code'] ?? null)) {
                throw ValidationException::withMessages([$giftCardField => 'Enter the gift card code.']);
            }

            if ($invoice->items()->where('item_type', 'gift_card')->exists()) {
                throw ValidationException::withMessages([$giftCardField => 'A gift card cannot be used to buy another gift card.']);
            }
        }

        return DB::transaction(function () use ($invoice, $method, $data, $user, $giftCardField) {
            // Lock the invoice so concurrent payments cannot both pass the balance check and overpay it.
            $invoice = Invoice::withoutTenantScope()->whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            if ($invoice->status === Invoice::STATUS_VOID) {
                throw ValidationException::withMessages([
                    'invoice' => 'Payments cannot be recorded against a void invoice.',
                ]);
            }

            $amount = min((float) $data['amount'], (float) $invoice->balance_amount);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => (float) $invoice->balance_amount <= 0
                        ? 'This invoice is already fully paid.'
                        : 'Enter a payment amount greater than zero.',
                ]);
            }

            $shift = app(CashRegisterService::class)->shiftForTransaction($invoice->tenant_id, $invoice->branch_id, $method, $data['error_field'] ?? 'payment_method_id');

            $payment = Payment::create([
                'tenant_id' => $invoice->tenant_id,
                'branch_id' => $invoice->branch_id,
                'sale_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'payment_number' => null,
                'payment_method_id' => $method->id,
                'cash_register_shift_id' => $shift?->id,
                'amount' => $amount,
                'method' => $method->code,
                'reference' => $data['transaction_reference'] ?? null,
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'gateway_reference' => $data['gateway_reference'] ?? null,
                'cash_received' => $data['cash_received'] ?? null,
                'change_given' => $method->type === PaymentMethod::TYPE_CASH
                    ? max(0, (float) ($data['cash_received'] ?? $amount) - $amount)
                    : null,
                'status' => Payment::STATUS_COMPLETED,
                'paid_at' => $data['paid_at'] ?? now(),
                'received_by' => $user->id,
                'cashier_staff_id' => $data['cashier_staff_id'] ?? null,
                'appointment_deposit_id' => $data['appointment_deposit_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $payment->update([
                'payment_number' => $this->paymentNumber($payment),
            ]);

            if ($method->type === PaymentMethod::TYPE_GIFT_CARD) {
                app(GiftCardService::class)->redeem($payment, (string) $data['gift_card_code'], $user, $giftCardField);
            }

            $this->settle($this->refreshInvoicePaymentStatus($invoice), $user);

            return $payment->fresh(['paymentMethod', 'receiver']);
        });
    }

    /**
     * For an invoice with nothing left to pay before any payment is taken, e.g. when package credits
     * cover every service. It still has to be marked paid and run the usual follow-up.
     */
    public function settleWithoutPayment(Invoice $invoice, User $user): Invoice
    {
        $invoice = $this->refreshInvoicePaymentStatus($invoice);
        $this->settle($invoice, $user);

        return $invoice;
    }

    private function settle(Invoice $invoice, User $user): void
    {
        if ($invoice->payment_status === Invoice::PAYMENT_PAID) {
            app(TipService::class)->markEarned($invoice);
        }

        app(CommissionGenerator::class)->generateForInvoice($invoice, $user);
        app(PromotionService::class)->recordUsageForInvoice($invoice, $user);

        // Gift card value earns points when it is spent, not when the card is bought.
        if ($invoice->payment_status === Invoice::PAYMENT_PAID && ! $invoice->items()->whereIn('item_type', ['membership', 'gift_card'])->exists()) {
            $earned = app(LoyaltyService::class)->earnFromInvoice($invoice->fresh(['tenant', 'customer', 'items.service']), $user);

            if ($earned && $invoice->customer && app(SettingService::class)->get('loyalty.notify_points_earned', $invoice->tenant_id, $invoice->branch_id)) {
                app(CustomerRewardsService::class)->sendBalance($invoice->customer, $user);
            }
        }

        if ($invoice->payment_status === Invoice::PAYMENT_PAID && $invoice->customer_id) {
            app(ReferralService::class)->rewardIfDue($invoice->fresh(['tenant', 'customer']), $user);
        }
    }

    /**
     * Records one payment per tender line of a split bill. A single line is capped at the balance;
     * in a split the excess comes off the cash lines as change. Card and other non-cash lines are never
     * reduced silently, because the customer was actually charged that amount.
     *
     * @param  array<int, array{index?: int|string, payment_method_id: int|string, amount: int|float|string, cash_received?: mixed, transaction_reference?: ?string, gateway_reference?: ?string}>  $lines
     */
    public function recordLines(Invoice $invoice, array $lines, ?string $notes, User $user, ?int $cashierStaffId = null): void
    {
        $lines = collect($lines)->values()->map(fn (array $line, int $position) => $line + ['index' => $position]);

        $methods = PaymentMethod::withoutTenantScope()
            ->where('tenant_id', $invoice->tenant_id)
            ->whereIn('id', $lines->pluck('payment_method_id'))
            ->get()
            ->keyBy('id');

        $lines = $lines->map(function (array $line) use ($methods) {
            $method = $methods->get((int) $line['payment_method_id']);

            if (! $method) {
                throw ValidationException::withMessages(["payments.{$line['index']}.payment_method_id" => 'Select an active payment method for this salon.']);
            }

            return $line + ['method' => $method];
        });

        $split = $lines->count() > 1;
        $balance = (float) Invoice::withoutTenantScope()->whereKey($invoice->getKey())->value('balance_amount');

        if ($split) {
            $nonCash = round((float) $lines->filter(fn (array $line) => $line['method']->type !== PaymentMethod::TYPE_CASH)->sum('amount'), 2);

            if ($nonCash > $balance + 0.009) {
                throw ValidationException::withMessages([
                    'payments' => 'Card and other non-cash payments add up to LKR ' . number_format($nonCash, 2) . ', more than the LKR ' . number_format($balance, 2) . ' due.',
                ]);
            }

            // Non-cash first, so any excess lands on cash, which can be given back as change.
            $lines = $lines->sortBy(fn (array $line) => $line['method']->type === PaymentMethod::TYPE_CASH ? 1 : 0)->values();
        }

        DB::transaction(function () use ($invoice, $lines, $split, $balance, $notes, $user, $cashierStaffId) {
            foreach ($lines as $line) {
                $amount = min((float) $line['amount'], $balance);

                if ($amount <= 0) {
                    continue;
                }

                $isCash = $line['method']->type === PaymentMethod::TYPE_CASH;

                $this->record($invoice, $line['method'], [
                    'amount' => $amount,
                    'transaction_reference' => $line['transaction_reference'] ?? null,
                    'gateway_reference' => $line['gateway_reference'] ?? null,
                    // When a split's cash share is reduced, the customer still handed over the original amount.
                    'cash_received' => $isCash && $split ? max((float) ($line['cash_received'] ?? 0), (float) $line['amount']) : ($line['cash_received'] ?? null),
                    'notes' => $notes,
                    'cashier_staff_id' => $cashierStaffId,
                    'gift_card_code' => $line['gift_card_code'] ?? null,
                    'gift_card_field' => "payments.{$line['index']}.gift_card_code",
                    'error_field' => "payments.{$line['index']}.payment_method_id",
                ], $user);

                $balance = round($balance - $amount, 2);
            }
        });
    }

    public function refreshInvoicePaymentStatus(Invoice $invoice): Invoice
    {
        $paidAmount = (float) $invoice->payments()
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');

        // Tips are owed on top of the sale total, so they are part of what has to be paid.
        $total = $invoice->amountDue();
        $balance = max(0, round($total - $paidAmount, 2));
        $paymentStatus = match (true) {
            $total <= 0.009 => Invoice::PAYMENT_PAID,
            $paidAmount <= 0 => Invoice::PAYMENT_UNPAID,
            $paidAmount < $total => Invoice::PAYMENT_PARTIAL,
            default => Invoice::PAYMENT_PAID,
        };

        $invoice->update([
            'paid_amount' => $paidAmount,
            'balance_amount' => $balance,
            'payment_status' => $paymentStatus,
            'paid_at' => $paymentStatus === Invoice::PAYMENT_PAID ? now() : null,
        ]);

        return $invoice->fresh(['payments.paymentMethod']);
    }

    private function paymentNumber(Payment $payment): string
    {
        return 'PAY-' . $payment->paid_at->format('Y') . '-' . str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);
    }
}
