<?php

namespace App\Services\Inventory;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReceipt;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Purchase orders and goods received notes (GRN).
 *
 * Draft -> Ordered -> (Partially) Received. Stock only moves when goods are received, one GRN per delivery.
 */
class PurchaseService
{
    public function __construct(private readonly InventoryService $inventory)
    {
    }

    public function create(Branch $branch, array $data, User $user): Purchase
    {
        return DB::transaction(function () use ($branch, $data, $user) {
            $purchase = Purchase::withoutTenantScope()->create($this->headerPayload($branch, $data) + [
                'tenant_id' => $branch->tenant_id,
                'status' => Purchase::STATUS_DRAFT,
                'created_by' => $user->id,
            ]);

            $purchase->update(['purchase_number' => $this->number('PO', $purchase->id)]);
            $this->syncItems($purchase, $data['items']);

            return $purchase->fresh(['items.product', 'supplier', 'branch']);
        });
    }

    public function update(Purchase $purchase, Branch $branch, array $data): Purchase
    {
        $this->ensureStatus($purchase, [Purchase::STATUS_DRAFT], 'Only draft purchase orders can be edited.');

        return DB::transaction(function () use ($purchase, $branch, $data) {
            $purchase->update($this->headerPayload($branch, $data));
            $purchase->items()->delete();
            $this->syncItems($purchase, $data['items']);

            return $purchase->fresh(['items.product', 'supplier', 'branch']);
        });
    }

    public function markOrdered(Purchase $purchase): Purchase
    {
        $this->ensureStatus($purchase, [Purchase::STATUS_DRAFT], 'Only draft purchase orders can be sent to the supplier.');

        if (! $purchase->items()->exists()) {
            throw ValidationException::withMessages(['items' => 'Add at least one product before ordering.']);
        }

        $purchase->update(['status' => Purchase::STATUS_ORDERED, 'ordered_at' => now()]);

        return $purchase;
    }

    public function cancel(Purchase $purchase): Purchase
    {
        $this->ensureStatus($purchase, [Purchase::STATUS_DRAFT, Purchase::STATUS_ORDERED], 'Only draft or ordered purchase orders can be cancelled.');

        $purchase->update(['status' => Purchase::STATUS_CANCELLED, 'cancelled_at' => now()]);

        return $purchase;
    }

    /**
     * Books one delivery in: stock goes up per received line (with batch/expiry for batch-tracked products)
     * and the order moves to partially received or received.
     *
     * @param  array<int, array{quantity?: int|string|null, batch_number?: ?string, expiry_date?: ?string}>  $lines  keyed by purchase item id
     */
    public function receive(Purchase $purchase, array $lines, User $user, array $meta = []): PurchaseReceipt
    {
        return DB::transaction(function () use ($purchase, $lines, $user, $meta) {
            /** @var Purchase $purchase */
            $purchase = Purchase::withoutTenantScope()->with('branch')->lockForUpdate()->findOrFail($purchase->id);

            $this->ensureStatus($purchase, Purchase::RECEIVABLE_STATUSES, 'Goods can only be received against an ordered purchase.');

            $items = PurchaseItem::withoutTenantScope()
                ->with('product')
                ->where('purchase_id', $purchase->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $toReceive = [];

            foreach ($lines as $itemId => $line) {
                $quantity = (int) ($line['quantity'] ?? 0);

                if ($quantity <= 0) {
                    continue;
                }

                $item = $items->get((int) $itemId);

                if (! $item) {
                    throw ValidationException::withMessages(['items' => 'A received line does not belong to this purchase order.']);
                }

                if ($quantity > $item->quantity_pending) {
                    throw ValidationException::withMessages([
                        "items.{$itemId}.quantity" => "Only {$item->quantity_pending} of {$item->product->name} are still expected.",
                    ]);
                }

                $toReceive[] = [$item, $quantity, $line];
            }

            if ($toReceive === []) {
                throw ValidationException::withMessages(['items' => 'Enter the quantity received for at least one product.']);
            }

            $receivedAt = isset($meta['received_at']) ? CarbonImmutable::parse($meta['received_at'])->setTimeFrom(now()) : now();

            $receipt = PurchaseReceipt::withoutTenantScope()->create([
                'tenant_id' => $purchase->tenant_id,
                'branch_id' => $purchase->branch_id,
                'purchase_id' => $purchase->id,
                'supplier_reference' => $meta['supplier_reference'] ?? null,
                'received_at' => $receivedAt,
                'notes' => $meta['notes'] ?? null,
                'received_by' => $user->id,
            ]);
            $receipt->update(['receipt_number' => $this->number('GRN', $receipt->id)]);

            foreach ($toReceive as [$item, $quantity, $line]) {
                /** @var PurchaseItem $item */
                $product = $item->product;
                $batchId = null;

                if ($product->track_inventory) {
                    $movement = $this->inventory->increase(
                        $product,
                        $purchase->branch,
                        $quantity,
                        StockMovement::TYPE_PURCHASE_RECEIPT,
                        (float) $item->unit_cost,
                        $user,
                        $receipt,
                        'Purchase receipt',
                        "{$receipt->receipt_number} / {$purchase->purchase_number}",
                        [
                            'batch_number' => $line['batch_number'] ?? null,
                            'expiry_date' => $line['expiry_date'] ?? null,
                        ]
                    );

                    $batchId = $movement->relationLoaded('batch') ? $movement->getRelation('batch')?->id : null;
                }

                $receipt->items()->create([
                    'tenant_id' => $purchase->tenant_id,
                    'purchase_item_id' => $item->id,
                    'product_id' => $product->id,
                    'product_batch_id' => $batchId,
                    'quantity' => $quantity,
                    'unit_cost' => $item->unit_cost,
                    'batch_number' => $line['batch_number'] ?? null,
                    'expiry_date' => $line['expiry_date'] ?? null,
                ]);

                $item->update(['quantity_received' => $item->quantity_received + $quantity]);
            }

            // The received lines above are the same instances as in $items, so their counts are current.
            $fullyReceived = $items->every(fn (PurchaseItem $item) => $item->quantity_pending === 0);

            $purchase->update([
                'status' => $fullyReceived ? Purchase::STATUS_RECEIVED : Purchase::STATUS_PARTIALLY_RECEIVED,
                'received_at' => $fullyReceived ? now() : null,
            ]);

            return $receipt->fresh(['items.product', 'purchase']);
        });
    }

    private function headerPayload(Branch $branch, array $data): array
    {
        $subtotal = collect($data['items'])->sum(fn (array $item) => round((int) $item['quantity'] * (float) $item['unit_cost'], 2));
        $discount = min($subtotal, (float) ($data['discount'] ?? 0));
        $tax = (float) ($data['tax'] ?? 0);

        if (! empty($data['supplier_id'])) {
            $belongs = Supplier::withoutTenantScope()->where('tenant_id', $branch->tenant_id)->whereKey($data['supplier_id'])->exists();

            if (! $belongs) {
                throw ValidationException::withMessages(['supplier_id' => 'The selected supplier does not belong to this salon.']);
            }
        }

        return [
            'branch_id' => $branch->id,
            'supplier_id' => $data['supplier_id'] ?? null,
            'invoice_number' => $data['invoice_number'] ?? null,
            'purchase_date' => $data['purchase_date'],
            'expected_date' => $data['expected_date'] ?? null,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => round($subtotal - $discount + $tax, 2),
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function syncItems(Purchase $purchase, array $items): void
    {
        $productIds = collect($items)->pluck('product_id')->map(fn ($id) => (int) $id);

        $validCount = Product::withoutTenantScope()
            ->where('tenant_id', $purchase->tenant_id)
            ->whereIn('id', $productIds->unique())
            ->count();

        if ($validCount !== $productIds->unique()->count()) {
            throw ValidationException::withMessages(['items' => 'One or more products do not belong to this salon.']);
        }

        foreach ($items as $item) {
            $purchase->items()->create([
                'tenant_id' => $purchase->tenant_id,
                'product_id' => (int) $item['product_id'],
                'quantity' => (int) $item['quantity'],
                'quantity_received' => 0,
                'unit_cost' => (float) $item['unit_cost'],
                'total' => round((int) $item['quantity'] * (float) $item['unit_cost'], 2),
            ]);
        }
    }

    private function ensureStatus(Purchase $purchase, array $statuses, string $message): void
    {
        if (! in_array($purchase->status, $statuses, true)) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }

    private function number(string $prefix, int $id): string
    {
        return $prefix . '-' . now()->format('Y') . '-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }
}
