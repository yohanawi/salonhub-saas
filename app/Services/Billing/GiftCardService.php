<?php

namespace App\Services\Billing;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\GiftCard;
use App\Models\GiftCardTransaction;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Gift vouchers are sold as an invoice line and spent later as a payment method. The card balance is
 * a ledger: every issue, redemption and reversal writes a transaction with the balance after it.
 */
class GiftCardService
{
    public function __construct(private readonly StandaloneSaleService $sales)
    {
    }

    public function sell(Branch $branch, ?Customer $customer, array $data, User $user, ?int $cashierStaffId = null): GiftCard
    {
        $amount = round((float) $data['amount'], 2);
        $code = filled($data['code'] ?? null) ? GiftCard::normalizeCode($data['code']) : $this->generateCode($branch->tenant_id);

        if ($this->find($branch->tenant_id, $code)) {
            throw ValidationException::withMessages(['code' => 'A gift card with this code already exists.']);
        }

        return DB::transaction(function () use ($branch, $customer, $data, $user, $cashierStaffId, $amount, $code) {
            $card = GiftCard::create([
                'tenant_id' => $branch->tenant_id,
                'branch_id' => $branch->id,
                'code' => $code,
                'initial_amount' => $amount,
                'balance' => $amount,
                'status' => GiftCard::STATUS_ACTIVE,
                'expires_at' => $data['expires_at'] ?? null,
                'customer_id' => $customer?->id,
                'recipient_name' => $data['recipient_name'] ?? null,
                'recipient_phone' => $data['recipient_phone'] ?? null,
                'message' => $data['message'] ?? null,
                'issued_by' => $user->id,
            ]);

            $invoice = $this->sales->sell($branch, $customer, [
                'item_type' => 'gift_card',
                'item_id' => $card->id,
                'item_name' => 'Gift Card ' . $card->code,
                'description' => $card->recipient_name ? 'For ' . $card->recipient_name : 'Gift card',
                'amount' => $amount,
            ], $data, $user, $cashierStaffId);

            $card->update(['invoice_id' => $invoice->id]);

            $this->writeTransaction($card, GiftCardTransaction::TYPE_ISSUE, $amount, $invoice, null, $user, 'Issued on ' . $invoice->invoice_number);

            return $card->fresh(['invoice', 'customer']);
        });
    }

    public function find(int $tenantId, ?string $code): ?GiftCard
    {
        $code = GiftCard::normalizeCode($code);

        return $code === '' ? null : GiftCard::withoutTenantScope()->where('tenant_id', $tenantId)->where('code', $code)->first();
    }

    /**
     * Why the card cannot pay this amount, or null when it can.
     */
    public function unusableReason(?GiftCard $card, float $amount): ?string
    {
        return match (true) {
            ! $card => 'Gift card not found. Check the code and try again.',
            $card->status === GiftCard::STATUS_VOID => 'This gift card has been voided.',
            $card->isExpired() => 'This gift card expired on ' . $card->expires_at->format('M d, Y') . '.',
            (float) $card->balance <= 0 => 'This gift card has no balance left.',
            $amount > (float) $card->balance + 0.009 => 'This gift card only has LKR ' . number_format((float) $card->balance, 2) . ' left.',
            default => null,
        };
    }

    /**
     * Called by PaymentService inside its transaction, once the payment row exists.
     */
    public function redeem(Payment $payment, string $code, User $user, string $field): void
    {
        $card = GiftCard::withoutTenantScope()
            ->where('tenant_id', $payment->tenant_id)
            ->where('code', GiftCard::normalizeCode($code))
            ->lockForUpdate()
            ->first();

        if ($reason = $this->unusableReason($card, (float) $payment->amount)) {
            throw ValidationException::withMessages([$field => $reason]);
        }

        $balance = round((float) $card->balance - (float) $payment->amount, 2);

        $card->update([
            'balance' => $balance,
            'status' => $balance <= 0 ? GiftCard::STATUS_REDEEMED : GiftCard::STATUS_ACTIVE,
        ]);

        $payment->update(['gift_card_id' => $card->id]);

        $this->writeTransaction($card, GiftCardTransaction::TYPE_REDEEM, -(float) $payment->amount, $payment->invoice, $payment, $user, 'Redeemed on ' . $payment->invoice?->invoice_number);
    }

    /**
     * Gives back what the voided invoice took off its gift cards.
     */
    public function reverseForInvoice(Invoice $invoice, User $user): void
    {
        $invoice->payments()
            ->whereNotNull('gift_card_id')
            ->get()
            ->each(function (Payment $payment) use ($invoice, $user) {
                $card = GiftCard::withoutTenantScope()->whereKey($payment->gift_card_id)->lockForUpdate()->first();

                if (! $card || $card->status === GiftCard::STATUS_VOID) {
                    return;
                }

                $balance = round((float) $card->balance + (float) $payment->amount, 2);
                $card->update(['balance' => $balance, 'status' => GiftCard::STATUS_ACTIVE]);

                $this->writeTransaction($card, GiftCardTransaction::TYPE_REVERSE, (float) $payment->amount, $invoice, $payment, $user, 'Invoice ' . $invoice->invoice_number . ' voided');
            });
    }

    /**
     * Voiding the invoice that sold a card cancels the card, which is only safe while it is unspent.
     */
    public function voidSoldOnInvoice(Invoice $invoice, User $user, string $reason): void
    {
        GiftCard::withoutTenantScope()
            ->where('invoice_id', $invoice->id)
            ->lockForUpdate()
            ->get()
            ->each(function (GiftCard $card) use ($user, $reason) {
                if ((float) $card->balance < (float) $card->initial_amount) {
                    throw ValidationException::withMessages([
                        'invoice' => "Gift card {$card->code} has already been used, so this sale cannot be voided.",
                    ]);
                }

                $this->void($card, $user, $reason);
            });
    }

    public function void(GiftCard $card, User $user, string $reason): GiftCard
    {
        return DB::transaction(function () use ($card, $user, $reason) {
            $card = GiftCard::withoutTenantScope()->whereKey($card->id)->lockForUpdate()->firstOrFail();

            if ($card->status === GiftCard::STATUS_VOID) {
                return $card;
            }

            $balance = (float) $card->balance;

            $card->update([
                'status' => GiftCard::STATUS_VOID,
                'balance' => 0,
                'voided_at' => now(),
                'voided_by' => $user->id,
                'void_reason' => $reason,
            ]);

            $this->writeTransaction($card, GiftCardTransaction::TYPE_VOID, -$balance, null, null, $user, Str::limit($reason, 250));

            return $card->fresh();
        });
    }

    public function generateCode(int $tenantId): string
    {
        // No 0/O or 1/I, so codes read back over the phone without mistakes.
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = 'GC-' . collect(range(1, 8))->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])->implode('');
            $code = substr($code, 0, 7) . '-' . substr($code, 7);
        } while ($this->find($tenantId, $code));

        return $code;
    }

    private function writeTransaction(GiftCard $card, string $type, float $amount, ?Invoice $invoice, ?Payment $payment, User $user, ?string $notes): void
    {
        GiftCardTransaction::create([
            'tenant_id' => $card->tenant_id,
            'gift_card_id' => $card->id,
            'type' => $type,
            'amount' => round($amount, 2),
            'balance_after' => $card->fresh()->balance,
            'invoice_id' => $invoice?->id,
            'payment_id' => $payment?->id,
            'created_by' => $user->id,
            'notes' => $notes,
        ]);
    }
}
