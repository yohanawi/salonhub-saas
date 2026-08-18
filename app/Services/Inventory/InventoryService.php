<?php

namespace App\Services\Inventory;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function openingStock(Product $product, Branch $branch, int $quantity, float $unitCost, ?User $user = null): ?StockMovement
    {
        if ($quantity <= 0 || ! $product->track_inventory) {
            return null;
        }

        return $this->increase($product, $branch, $quantity, StockMovement::TYPE_OPENING_STOCK, $unitCost, $user, null, 'Opening stock');
    }

    public function increase(
        Product $product,
        Branch $branch,
        int $quantity,
        string $type,
        float $unitCost = 0,
        ?User $user = null,
        ?Model $reference = null,
        ?string $reason = null,
        ?string $notes = null
    ): StockMovement {
        $this->ensureSameTenant($product, $branch);
        $this->ensurePositiveQuantity($quantity);

        return DB::transaction(function () use ($product, $branch, $quantity, $type, $unitCost, $user, $reference, $reason, $notes) {
            $stock = $this->stockForUpdate($product, $branch);
            $before = (int) $stock->quantity_on_hand;
            $after = $before + $quantity;
            $averageCost = $this->weightedAverageCost($stock, $quantity, $unitCost);

            $stock->update([
                'quantity' => $after,
                'quantity_on_hand' => $after,
                'average_cost' => $averageCost,
                'last_received_at' => in_array($type, [StockMovement::TYPE_OPENING_STOCK, StockMovement::TYPE_PURCHASE_RECEIPT, StockMovement::TYPE_TRANSFER_IN, StockMovement::TYPE_SALE_RETURN], true) ? now() : $stock->last_received_at,
                'last_adjusted_at' => str_starts_with($type, 'adjustment_') ? now() : $stock->last_adjusted_at,
            ]);

            return $this->movement($product, $branch, $type, $quantity, $before, $after, $unitCost ?: (float) $stock->average_cost, $user, $reference, $reason, $notes);
        });
    }

    public function decrease(
        Product $product,
        Branch $branch,
        int $quantity,
        string $type,
        ?User $user = null,
        ?Model $reference = null,
        ?string $reason = null,
        ?string $notes = null
    ): StockMovement {
        $this->ensureSameTenant($product, $branch);
        $this->ensurePositiveQuantity($quantity);

        return DB::transaction(function () use ($product, $branch, $quantity, $type, $user, $reference, $reason, $notes) {
            $stock = $this->stockForUpdate($product, $branch);
            $before = (int) $stock->quantity_on_hand;
            $after = $before - $quantity;

            if ($after < 0 && ! $product->allow_negative_stock) {
                throw ValidationException::withMessages([
                    'stock' => "Only {$before} units of {$product->name} are available at {$branch->name}.",
                ]);
            }

            $stock->update([
                'quantity' => $after,
                'quantity_on_hand' => $after,
                'last_sold_at' => $type === StockMovement::TYPE_POS_SALE ? now() : $stock->last_sold_at,
                'last_adjusted_at' => str_starts_with($type, 'adjustment_') || in_array($type, [StockMovement::TYPE_DAMAGED, StockMovement::TYPE_EXPIRED, StockMovement::TYPE_LOST], true) ? now() : $stock->last_adjusted_at,
            ]);

            return $this->movement($product, $branch, $type, -$quantity, $before, $after, (float) $stock->average_cost, $user, $reference, $reason, $notes);
        });
    }

    public function adjustStock(Branch $branch, array $items, string $reason, ?string $notes, User $user): StockAdjustment
    {
        return DB::transaction(function () use ($branch, $items, $reason, $notes, $user) {
            $adjustment = StockAdjustment::create([
                'tenant_id' => $branch->tenant_id,
                'branch_id' => $branch->id,
                'adjustment_number' => 'ADJ-TMP-' . uniqid(),
                'reason' => $reason,
                'notes' => $notes,
                'status' => StockAdjustment::STATUS_APPROVED,
                'created_by' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            $adjustment->update([
                'adjustment_number' => $this->adjustmentNumber($adjustment),
            ]);

            foreach ($items as $item) {
                $product = Product::withoutTenantScope()
                    ->where('tenant_id', $branch->tenant_id)
                    ->findOrFail($item['product_id']);

                $stock = $this->stockForUpdate($product, $branch);
                $systemQuantity = (int) $stock->quantity_on_hand;
                $actualQuantity = (int) $item['actual_quantity'];
                $difference = $actualQuantity - $systemQuantity;

                $adjustment->items()->create([
                    'tenant_id' => $branch->tenant_id,
                    'product_id' => $product->id,
                    'system_quantity' => $systemQuantity,
                    'actual_quantity' => $actualQuantity,
                    'difference' => $difference,
                    'reason' => $item['reason'] ?? $reason,
                ]);

                if ($difference > 0) {
                    $this->increase($product, $branch, $difference, StockMovement::TYPE_ADJUSTMENT_IN, (float) $stock->average_cost, $user, $adjustment, $reason, $notes);
                } elseif ($difference < 0) {
                    $this->decrease($product, $branch, abs($difference), StockMovement::TYPE_ADJUSTMENT_OUT, $user, $adjustment, $reason, $notes);
                }
            }

            return $adjustment->fresh(['branch', 'items.product', 'createdBy', 'approvedBy']);
        });
    }

    public function deductInvoiceProducts(Invoice $invoice, User $user): void
    {
        $invoice->loadMissing(['branch', 'items.product']);

        if (StockMovement::withoutTenantScope()
            ->where('tenant_id', $invoice->tenant_id)
            ->where('type', StockMovement::TYPE_POS_SALE)
            ->where('reference_type', $invoice->getMorphClass())
            ->where('reference_id', $invoice->id)
            ->exists()) {
            return;
        }

        DB::transaction(function () use ($invoice, $user) {
            foreach ($invoice->items->where('item_type', 'product') as $item) {
                $product = $item->product;

                if (! $product || ! $product->track_inventory) {
                    continue;
                }

                $this->decrease(
                    $product,
                    $invoice->branch,
                    (int) $item->quantity,
                    StockMovement::TYPE_POS_SALE,
                    $user,
                    $invoice,
                    'POS sale',
                    $invoice->invoice_number
                );
            }
        });
    }

    public function returnInvoiceProducts(Invoice $invoice, User $user, ?string $reason = null): void
    {
        $invoice->loadMissing(['branch', 'items.product']);

        if (StockMovement::withoutTenantScope()
            ->where('tenant_id', $invoice->tenant_id)
            ->where('type', StockMovement::TYPE_SALE_RETURN)
            ->where('reference_type', $invoice->getMorphClass())
            ->where('reference_id', $invoice->id)
            ->exists()) {
            return;
        }

        DB::transaction(function () use ($invoice, $user, $reason) {
            foreach ($invoice->items->where('item_type', 'product') as $item) {
                $product = $item->product;

                if (! $product || ! $product->track_inventory) {
                    continue;
                }

                $this->increase(
                    $product,
                    $invoice->branch,
                    (int) $item->quantity,
                    StockMovement::TYPE_SALE_RETURN,
                    (float) $product->cost_price,
                    $user,
                    $invoice,
                    $reason ?: 'Invoice void',
                    $invoice->invoice_number
                );
            }
        });
    }

    private function stockForUpdate(Product $product, Branch $branch): Inventory
    {
        $stock = Inventory::withoutTenantScope()
            ->where('tenant_id', $product->tenant_id)
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->lockForUpdate()
            ->first();

        if ($stock) {
            return $stock;
        }

        return Inventory::withoutTenantScope()->create([
            'tenant_id' => $product->tenant_id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity' => 0,
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
            'average_cost' => (float) $product->cost_price,
        ]);
    }

    private function movement(
        Product $product,
        Branch $branch,
        string $type,
        int $quantity,
        int $before,
        int $after,
        float $unitCost,
        ?User $user,
        ?Model $reference,
        ?string $reason,
        ?string $notes
    ): StockMovement {
        return StockMovement::create([
            'tenant_id' => $product->tenant_id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'type' => $type,
            'quantity' => $quantity,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'unit_cost' => $unitCost,
            'total_cost' => abs($quantity) * $unitCost,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'reason' => $reason,
            'notes' => $notes,
            'created_by' => $user?->id,
        ]);
    }

    private function weightedAverageCost(Inventory $stock, int $incomingQuantity, float $incomingCost): float
    {
        if ($incomingCost <= 0) {
            return (float) $stock->average_cost;
        }

        $currentQuantity = max(0, (int) $stock->quantity_on_hand);
        $newQuantity = $currentQuantity + $incomingQuantity;

        if ($newQuantity <= 0) {
            return $incomingCost;
        }

        $currentValue = $currentQuantity * (float) $stock->average_cost;
        $incomingValue = $incomingQuantity * $incomingCost;

        return round(($currentValue + $incomingValue) / $newQuantity, 2);
    }

    private function adjustmentNumber(StockAdjustment $adjustment): string
    {
        return 'ADJ-' . now()->format('Y') . '-' . str_pad((string) $adjustment->id, 6, '0', STR_PAD_LEFT);
    }

    private function ensureSameTenant(Product $product, Branch $branch): void
    {
        if ((int) $product->tenant_id !== (int) $branch->tenant_id) {
            throw ValidationException::withMessages([
                'branch_id' => 'This branch does not belong to the same salon as the selected product.',
            ]);
        }
    }

    private function ensurePositiveQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be greater than zero.',
            ]);
        }
    }
}
