<?php

namespace App\Services\Inventory;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Draft -> In transit (stock leaves the sending branch) -> Received (stock arrives, possibly short).
 * Cancelling a transfer in transit puts the stock back at the sending branch.
 */
class StockTransferService
{
    public function __construct(private readonly InventoryService $inventory)
    {
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    public function create(Branch $from, Branch $to, array $items, ?string $notes, User $user): StockTransfer
    {
        if ((int) $from->id === (int) $to->id) {
            throw ValidationException::withMessages(['to_branch_id' => 'Choose a different branch to send stock to.']);
        }

        if ((int) $from->tenant_id !== (int) $to->tenant_id) {
            throw ValidationException::withMessages(['to_branch_id' => 'Both branches must belong to the same salon.']);
        }

        return DB::transaction(function () use ($from, $to, $items, $notes, $user) {
            $transfer = StockTransfer::withoutTenantScope()->create([
                'tenant_id' => $from->tenant_id,
                'from_branch_id' => $from->id,
                'to_branch_id' => $to->id,
                'status' => StockTransfer::STATUS_DRAFT,
                'notes' => $notes,
                'created_by' => $user->id,
            ]);
            $transfer->update(['transfer_number' => 'TRF-' . now()->format('Y') . '-' . str_pad((string) $transfer->id, 6, '0', STR_PAD_LEFT)]);

            $productIds = collect($items)->pluck('product_id')->map(fn ($id) => (int) $id);
            $valid = Product::withoutTenantScope()->where('tenant_id', $from->tenant_id)->where('track_inventory', true)->whereIn('id', $productIds)->count();

            if ($valid !== $productIds->unique()->count()) {
                throw ValidationException::withMessages(['items' => 'Only stock-tracked products of this salon can be transferred.']);
            }

            foreach ($items as $item) {
                $transfer->items()->create([
                    'tenant_id' => $from->tenant_id,
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (int) $item['quantity'],
                ]);
            }

            return $transfer;
        });
    }

    public function send(StockTransfer $transfer, User $user): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $user) {
            $transfer = $this->lock($transfer, [StockTransfer::STATUS_DRAFT], 'Only draft transfers can be sent.');
            $transfer->loadMissing(['fromBranch', 'items.product']);

            foreach ($transfer->items as $item) {
                $cost = (float) (Inventory::withoutTenantScope()
                    ->where('branch_id', $transfer->from_branch_id)
                    ->where('product_id', $item->product_id)
                    ->value('average_cost') ?? $item->product->cost_price);

                // Throws (and rolls the whole transfer back) when the sending branch does not have enough.
                $this->inventory->decrease($item->product, $transfer->fromBranch, $item->quantity, StockMovement::TYPE_TRANSFER_OUT, $user, $transfer, 'Stock transfer out', $transfer->transfer_number);

                $item->update(['unit_cost' => $cost]);
            }

            $transfer->update(['status' => StockTransfer::STATUS_IN_TRANSIT, 'sent_by' => $user->id, 'sent_at' => now()]);

            return $transfer;
        });
    }

    /**
     * @param  array<int, int|string|null>  $received  quantity received keyed by transfer item id; missing = full quantity
     */
    public function receive(StockTransfer $transfer, array $received, User $user): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $received, $user) {
            $transfer = $this->lock($transfer, [StockTransfer::STATUS_IN_TRANSIT], 'Only transfers in transit can be received.');
            $transfer->loadMissing(['toBranch', 'items.product']);
            $shortages = [];

            foreach ($transfer->items as $item) {
                $quantity = array_key_exists($item->id, $received) && $received[$item->id] !== null && $received[$item->id] !== ''
                    ? (int) $received[$item->id]
                    : $item->quantity;

                if ($quantity < 0 || $quantity > $item->quantity) {
                    throw ValidationException::withMessages(["received.{$item->id}" => "Received quantity for {$item->product->name} must be between 0 and {$item->quantity}."]);
                }

                if ($quantity > 0) {
                    $this->inventory->increase($item->product, $transfer->toBranch, $quantity, StockMovement::TYPE_TRANSFER_IN, (float) $item->unit_cost, $user, $transfer, 'Stock transfer in', $transfer->transfer_number);
                }

                if ($quantity < $item->quantity) {
                    $shortages[] = "{$item->product->name}: " . ($item->quantity - $quantity) . ' short';
                }

                $item->update(['quantity_received' => $quantity]);
            }

            $transfer->update([
                'status' => StockTransfer::STATUS_RECEIVED,
                'received_by' => $user->id,
                'received_at' => now(),
                'notes' => trim(($transfer->notes ? $transfer->notes . "\n" : '') . ($shortages ? 'Received short: ' . implode('; ', $shortages) : '')) ?: null,
            ]);

            return $transfer;
        });
    }

    public function cancel(StockTransfer $transfer, User $user): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $user) {
            $transfer = $this->lock($transfer, [StockTransfer::STATUS_DRAFT, StockTransfer::STATUS_IN_TRANSIT], 'This transfer can no longer be cancelled.');

            if ($transfer->status === StockTransfer::STATUS_IN_TRANSIT) {
                $transfer->loadMissing(['fromBranch', 'items.product']);

                foreach ($transfer->items as $item) {
                    $this->inventory->increase($item->product, $transfer->fromBranch, $item->quantity, StockMovement::TYPE_TRANSFER_IN, (float) $item->unit_cost, $user, $transfer, 'Transfer cancelled: stock returned', $transfer->transfer_number);
                }
            }

            $transfer->update(['status' => StockTransfer::STATUS_CANCELLED, 'cancelled_at' => now()]);

            return $transfer;
        });
    }

    private function lock(StockTransfer $transfer, array $statuses, string $message): StockTransfer
    {
        $locked = StockTransfer::withoutTenantScope()->lockForUpdate()->findOrFail($transfer->id);

        if (! in_array($locked->status, $statuses, true)) {
            throw ValidationException::withMessages(['status' => $message]);
        }

        return $locked;
    }
}
