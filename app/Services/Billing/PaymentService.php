<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\Commission\CommissionGenerator;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Promotions\PromotionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function record(Invoice $invoice, PaymentMethod $method, array $data, User $user): Payment
    {
        if ($invoice->status === Invoice::STATUS_VOID) {
            throw ValidationException::withMessages([
                'invoice' => 'Payments cannot be recorded against a void invoice.',
            ]);
        }

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

        $amount = min((float) $data['amount'], (float) $invoice->balance_amount);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Enter a payment amount greater than zero.',
            ]);
        }

        return DB::transaction(function () use ($invoice, $method, $data, $user, $amount) {
            $payment = Payment::create([
                'tenant_id' => $invoice->tenant_id,
                'branch_id' => $invoice->branch_id,
                'sale_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'payment_number' => null,
                'payment_method_id' => $method->id,
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
                'notes' => $data['notes'] ?? null,
            ]);

            $payment->update([
                'payment_number' => $this->paymentNumber($payment),
            ]);

            $invoice = $this->refreshInvoicePaymentStatus($invoice);

            app(CommissionGenerator::class)->generateForInvoice($invoice, $user);
            app(PromotionService::class)->recordUsageForInvoice($invoice, $user);

            if ($invoice->payment_status === Invoice::PAYMENT_PAID && ! $invoice->items()->where('item_type', 'membership')->exists()) {
                app(LoyaltyService::class)->earnFromInvoice($invoice->fresh(['tenant', 'customer', 'items.service']), $user);
            }

            return $payment->fresh(['paymentMethod', 'receiver']);
        });
    }

    public function refreshInvoicePaymentStatus(Invoice $invoice): Invoice
    {
        $paidAmount = (float) $invoice->payments()
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');

        $total = (float) $invoice->total;
        $balance = max(0, $total - $paidAmount);
        $paymentStatus = match (true) {
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
