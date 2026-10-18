<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentMethod;
use App\Models\Refund;
use App\Models\RefundItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Commission\CommissionGenerator;
use App\Services\Inventory\InventoryService;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Membership\MembershipBenefitService;
use App\Services\Promotions\PromotionService;
use App\Services\Settings\SettingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundService
{
    /** Pending and completed refunds hold their items and amount, so two requests cannot over-refund. */
    private const OPEN_STATUSES = [Refund::STATUS_PENDING, Refund::STATUS_COMPLETED];

    public function __construct(
        private readonly InventoryService $inventory,
        private readonly CommissionGenerator $commissions,
        private readonly LoyaltyService $loyalty,
        private readonly PromotionService $promotions,
        private readonly MembershipBenefitService $membershipBenefits,
        private readonly SettingService $settings,
        private readonly CashRegisterService $cashRegister
    ) {
    }

    /**
     * Lines that can still be returned, with what one unit is worth after the invoice-level
     * discount and loyalty redemption are spread across lines.
     */
    public function returnableItems(Invoice $invoice): Collection
    {
        $invoice->loadMissing(['items.product', 'items.refundItems.refund']);
        $lineTotal = (float) $invoice->items->sum('total_amount');
        $ratio = $lineTotal > 0 ? (float) $invoice->total / $lineTotal : 0;

        return $invoice->items
            ->whereIn('item_type', ['service', 'product'])
            ->map(function (InvoiceItem $item) use ($ratio) {
                $returned = (int) $item->refundItems
                    ->filter(fn (RefundItem $refundItem) => in_array($refundItem->refund?->status, self::OPEN_STATUSES, true))
                    ->sum('quantity');

                return [
                    'item' => $item,
                    'returned' => $returned,
                    'remaining' => max(0, (int) $item->quantity - $returned),
                    'unit_value' => round((float) $item->total_amount / max(1, (int) $item->quantity) * $ratio, 2),
                    'restockable' => $item->item_type === 'product' && (bool) $item->product?->track_inventory,
                ];
            })
            ->values();
    }

    public function refundableAmount(Invoice $invoice): float
    {
        $held = (float) $invoice->refunds()->whereIn('status', self::OPEN_STATUSES)->sum('amount');

        return max(0, round((float) $invoice->paid_amount - $held, 2));
    }

    public function request(Invoice $invoice, array $data, User $user): Refund
    {
        return DB::transaction(function () use ($invoice, $data, $user) {
            $invoice = Invoice::withoutTenantScope()->whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            $this->ensureRefundable($invoice);

            $method = PaymentMethod::withoutTenantScope()
                ->where('tenant_id', $invoice->tenant_id)
                ->where('is_active', true)
                ->find($data['payment_method_id'] ?? null);

            if (! $method) {
                throw ValidationException::withMessages(['payment_method_id' => 'Select an active refund method for this salon.']);
            }

            if ($method->requires_reference && blank($data['transaction_reference'] ?? null)) {
                throw ValidationException::withMessages(['transaction_reference' => 'A reference is required for this refund method.']);
            }

            $lines = $this->returnLines($invoice, $data['items'] ?? []);
            $amount = round((float) ($data['amount'] ?? 0), 2);
            $refundable = $this->refundableAmount($invoice);

            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Enter a refund amount greater than zero.']);
            }

            if ($amount > $refundable) {
                throw ValidationException::withMessages([
                    'amount' => 'The refund cannot be more than LKR ' . number_format($refundable, 2) . ', the amount paid and not yet refunded.',
                ]);
            }

            $refund = Refund::create([
                'tenant_id' => $invoice->tenant_id,
                'branch_id' => $invoice->branch_id,
                'sale_id' => $invoice->id,
                'payment_method_id' => $method->id,
                'refund_number' => 'REF-TMP-' . uniqid(),
                'amount' => $amount,
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'reason' => $data['reason'],
                'status' => Refund::STATUS_PENDING,
                'requested_by' => $user->id,
            ]);

            $refund->update(['refund_number' => 'REF-' . $refund->created_at->format('Y') . '-' . str_pad((string) $refund->id, 6, '0', STR_PAD_LEFT)]);

            $lines->each(fn (array $line) => $refund->items()->create([
                'tenant_id' => $invoice->tenant_id,
                'sale_item_id' => $line['item']->id,
                'quantity' => $line['quantity'],
                'amount' => round($line['unit_value'] * $line['quantity'], 2),
                'restock' => $line['restock'],
            ]));

            // Someone allowed to approve refunds does not need a second person to sign their own request
            // off. Approving in the same transaction means a failed approval leaves no pending request.
            return $user->can('approveRefund', $invoice)
                ? $this->approve($refund, $user)
                : $refund->fresh(['items', 'paymentMethod']);
        });
    }

    public function approve(Refund $refund, User $user): Refund
    {
        return DB::transaction(function () use ($refund, $user) {
            $invoice = Invoice::withoutTenantScope()->whereKey($refund->sale_id)->lockForUpdate()->firstOrFail();
            $refund = Refund::withoutTenantScope()->whereKey($refund->getKey())->lockForUpdate()->firstOrFail();

            if ($refund->status !== Refund::STATUS_PENDING) {
                throw ValidationException::withMessages(['refund' => 'Only pending refunds can be approved.']);
            }

            if ($invoice->status === Invoice::STATUS_VOID) {
                throw ValidationException::withMessages(['refund' => 'Refunds cannot be completed on a void invoice.']);
            }

            $refundedAmount = round((float) $invoice->refunded_amount + (float) $refund->amount, 2);

            if ($refundedAmount > (float) $invoice->paid_amount) {
                throw ValidationException::withMessages(['refund' => 'This refund would exceed the amount paid on the invoice.']);
            }

            // The money leaves the drawer when the refund is completed, so that is the shift it counts against.
            $method = PaymentMethod::withoutTenantScope()->find($refund->payment_method_id);
            $shift = $method ? $this->cashRegister->shiftForTransaction($invoice->tenant_id, $invoice->branch_id, $method, 'refund') : null;

            $refund->update([
                'status' => Refund::STATUS_COMPLETED,
                'processed_by' => $user->id,
                'processed_at' => now(),
                'cash_register_shift_id' => $shift?->id,
            ]);

            $fullyRefunded = $refundedAmount >= (float) $invoice->paid_amount;

            $invoice->update([
                'refunded_amount' => $refundedAmount,
                'payment_status' => $fullyRefunded ? Invoice::PAYMENT_REFUNDED : Invoice::PAYMENT_PARTIALLY_REFUNDED,
                'updated_by' => $user->id,
            ]);

            $refund->setRelation('invoice', $invoice);
            $refund->load(['items.invoiceItem.product']);

            foreach ($refund->items as $refundItem) {
                $item = $refundItem->invoiceItem;

                if ($refundItem->restock) {
                    $this->restock($refund, $invoice, $item, $refundItem->quantity, $user);
                }

                $this->commissions->reverseForRefundItem($refund, $item, $refundItem->quantity, $user);
            }

            $this->loyalty->reverseForRefund($refund, $user);

            // Promotion and membership allowances are only given back once nothing of the sale remains.
            if ($fullyRefunded) {
                $this->promotions->reverseForInvoice($invoice, $user, $refund->reason);
                $this->membershipBenefits->reverseInvoiceUsage($invoice);
            }

            return $refund->fresh(['items', 'paymentMethod', 'processedBy']);
        });
    }

    public function reject(Refund $refund, User $user, string $reason): Refund
    {
        return DB::transaction(function () use ($refund, $user, $reason) {
            $refund = Refund::withoutTenantScope()->whereKey($refund->getKey())->lockForUpdate()->firstOrFail();

            if ($refund->status !== Refund::STATUS_PENDING) {
                throw ValidationException::withMessages(['refund' => 'Only pending refunds can be rejected.']);
            }

            $refund->update([
                'status' => Refund::STATUS_REJECTED,
                'processed_by' => $user->id,
                'processed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            return $refund;
        });
    }

    private function ensureRefundable(Invoice $invoice): void
    {
        if (! $this->settings->get('pos.allow_refunds', $invoice->tenant_id, $invoice->branch_id)) {
            throw ValidationException::withMessages(['refund' => 'Refunds are disabled for this salon.']);
        }

        if ($invoice->status !== Invoice::STATUS_ISSUED) {
            throw ValidationException::withMessages(['refund' => 'Only issued invoices can be refunded.']);
        }

        if (! in_array($invoice->payment_status, [Invoice::PAYMENT_PAID, Invoice::PAYMENT_PARTIALLY_REFUNDED], true)) {
            throw ValidationException::withMessages([
                'refund' => $invoice->payment_status === Invoice::PAYMENT_REFUNDED
                    ? 'This invoice has already been fully refunded.'
                    : 'Only fully paid invoices can be refunded. Void the invoice instead.',
            ]);
        }
    }

    private function returnLines(Invoice $invoice, array $requested): Collection
    {
        $returnable = $this->returnableItems($invoice)->keyBy(fn (array $line) => $line['item']->id);

        return collect($requested)
            ->filter(fn ($line) => is_array($line) && (int) ($line['quantity'] ?? 0) > 0)
            ->map(function (array $line, int|string $itemId) use ($returnable) {
                $available = $returnable->get((int) $itemId);

                if (! $available) {
                    throw ValidationException::withMessages(["items.{$itemId}.quantity" => 'This line cannot be returned.']);
                }

                $quantity = (int) $line['quantity'];

                if ($quantity > $available['remaining']) {
                    throw ValidationException::withMessages([
                        "items.{$itemId}.quantity" => "Only {$available['remaining']} of {$available['item']->item_name} can still be returned.",
                    ]);
                }

                return $available + [
                    'quantity' => $quantity,
                    'restock' => $available['restockable'] && filter_var($line['restock'] ?? false, FILTER_VALIDATE_BOOL),
                ];
            })
            ->values();
    }

    /**
     * Puts back only what the sale actually took out of stock, at the cost it left with.
     */
    private function restock(Refund $refund, Invoice $invoice, InvoiceItem $item, int $quantity, User $user): void
    {
        if (! $item->product) {
            return;
        }

        $sale = StockMovement::withoutTenantScope()
            ->where('tenant_id', $invoice->tenant_id)
            ->where('type', StockMovement::TYPE_POS_SALE)
            ->where('reference_type', $invoice->getMorphClass())
            ->where('reference_id', $invoice->id)
            ->where('product_id', $item->product_id)
            ->first();

        if (! $sale) {
            return;
        }

        $alreadyReturned = (int) StockMovement::withoutTenantScope()
            ->where('tenant_id', $invoice->tenant_id)
            ->where('type', StockMovement::TYPE_SALE_RETURN)
            ->where('reference_type', $refund->getMorphClass())
            ->whereIn('reference_id', $invoice->refunds()->select('id'))
            ->where('product_id', $item->product_id)
            ->sum('quantity');

        $quantity = min($quantity, abs((int) $sale->quantity) - $alreadyReturned);

        if ($quantity <= 0) {
            return;
        }

        $this->inventory->increase(
            $item->product,
            $invoice->branch,
            $quantity,
            StockMovement::TYPE_SALE_RETURN,
            (float) $sale->unit_cost,
            $user,
            $refund,
            $refund->reason,
            $refund->refund_number
        );
    }
}
