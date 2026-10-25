<?php

namespace App\Services\Inventory;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockTake;
use App\Models\StockTakeItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Physical stock counts. Starting a count snapshots the system quantity of every product in scope;
 * completing it posts only the counted differences, so sales made while counting are not wiped out.
 */
class StockTakeService
{
    public function __construct(private readonly InventoryService $inventory)
    {
    }

    public function start(Branch $branch, ?int $categoryId, ?string $notes, User $user): StockTake
    {
        return DB::transaction(function () use ($branch, $categoryId, $notes, $user) {
            $products = Product::withoutTenantScope()
                ->where('tenant_id', $branch->tenant_id)
                ->where('track_inventory', true)
                ->where('is_active', true)
                ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
                ->orderBy('name')
                ->get(['id']);

            if ($products->isEmpty()) {
                throw ValidationException::withMessages(['category_id' => 'There are no stock-tracked products to count for this selection.']);
            }

            $onHand = Inventory::withoutTenantScope()
                ->where('tenant_id', $branch->tenant_id)
                ->where('branch_id', $branch->id)
                ->whereIn('product_id', $products->pluck('id'))
                ->pluck('quantity_on_hand', 'product_id');

            $stockTake = StockTake::withoutTenantScope()->create([
                'tenant_id' => $branch->tenant_id,
                'branch_id' => $branch->id,
                'product_category_id' => $categoryId,
                'status' => StockTake::STATUS_IN_PROGRESS,
                'notes' => $notes,
                'started_at' => now(),
                'created_by' => $user->id,
            ]);

            $stockTake->update(['stock_take_number' => 'STK-' . now()->format('Y') . '-' . str_pad((string) $stockTake->id, 6, '0', STR_PAD_LEFT)]);

            foreach ($products as $product) {
                $stockTake->items()->create([
                    'tenant_id' => $branch->tenant_id,
                    'product_id' => $product->id,
                    'system_quantity' => (int) ($onHand[$product->id] ?? 0),
                ]);
            }

            return $stockTake;
        });
    }

    /**
     * @param  array<int, int|string|null>  $counts  counted quantity keyed by stock take item id; blank = not counted
     */
    public function saveCounts(StockTake $stockTake, array $counts): void
    {
        $this->ensureOpen($stockTake);

        DB::transaction(function () use ($stockTake, $counts) {
            $stockTake->items()->get()->each(function (StockTakeItem $item) use ($counts) {
                if (! array_key_exists($item->id, $counts)) {
                    return;
                }

                $value = $counts[$item->id];
                $counted = ($value === null || $value === '') ? null : max(0, (int) $value);

                $item->update([
                    'counted_quantity' => $counted,
                    'variance' => $counted === null ? null : $counted - $item->system_quantity,
                ]);
            });
        });
    }

    public function complete(StockTake $stockTake, User $user): StockTake
    {
        $this->ensureOpen($stockTake);

        return DB::transaction(function () use ($stockTake, $user) {
            $stockTake->loadMissing(['branch', 'items']);

            $adjustments = $stockTake->items
                ->filter(fn (StockTakeItem $item) => $item->counted_quantity !== null && (int) $item->variance !== 0)
                ->map(function (StockTakeItem $item) use ($stockTake) {
                    $current = (int) Inventory::withoutTenantScope()
                        ->where('tenant_id', $stockTake->tenant_id)
                        ->where('branch_id', $stockTake->branch_id)
                        ->where('product_id', $item->product_id)
                        ->value('quantity_on_hand');

                    return [
                        'product_id' => $item->product_id,
                        // Apply the counted difference on top of today's quantity.
                        'actual_quantity' => max(0, $current + (int) $item->variance),
                        'reason' => "Stock take {$stockTake->stock_take_number}",
                    ];
                })
                ->values()
                ->all();

            $adjustment = $adjustments === []
                ? null
                : $this->inventory->adjustStock(
                    $stockTake->branch,
                    $adjustments,
                    StockAdjustment::REASON_PHYSICAL_COUNT,
                    "Posted from stock take {$stockTake->stock_take_number}.",
                    $user
                );

            $stockTake->update([
                'status' => StockTake::STATUS_COMPLETED,
                'completed_at' => now(),
                'completed_by' => $user->id,
                'stock_adjustment_id' => $adjustment?->id,
            ]);

            return $stockTake;
        });
    }

    public function cancel(StockTake $stockTake): StockTake
    {
        $this->ensureOpen($stockTake);

        $stockTake->update(['status' => StockTake::STATUS_CANCELLED, 'cancelled_at' => now()]);

        return $stockTake;
    }

    private function ensureOpen(StockTake $stockTake): void
    {
        if (! $stockTake->isOpen()) {
            throw ValidationException::withMessages(['status' => 'This stock take is already closed.']);
        }
    }
}
