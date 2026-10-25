<?php

namespace App\Services\Inventory;

use App\Models\Inventory;
use App\Models\ProductBatch;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\InventoryAlertNotification;
use App\Services\BranchContext;
use App\Services\PlanEntitlementService;
use App\Services\Settings\SettingService;
use Illuminate\Support\Collection;

/**
 * Finds stock that needs attention and emails the people who look after it, once per incident:
 * a product is flagged again only after it was restocked above its reorder level, and a batch once.
 */
class InventoryAlertService
{
    public const EXPIRY_WARNING_DAYS = 30;

    public function __construct(
        private readonly PlanEntitlementService $entitlements,
        private readonly BranchContext $branchContext,
    ) {
    }

    /**
     * @return array{low_stock: int, expiring: int, recipients: int}
     */
    public function sendForTenant(Tenant $tenant): array
    {
        if (! $this->entitlements->featureEnabled($tenant, 'inventory')) {
            return ['low_stock' => 0, 'expiring' => 0, 'recipients' => 0];
        }

        // Settings > Inventory > Low Stock Alerts turns the digest off for the salon.
        if (! app(SettingService::class)->get('inventory.low_stock_alerts', $tenant->id)) {
            return ['low_stock' => 0, 'expiring' => 0, 'recipients' => 0];
        }

        $lowStock = $this->pendingLowStock($tenant);
        $expiring = $this->pendingExpiring($tenant);

        if ($lowStock->isEmpty() && $expiring->isEmpty()) {
            return ['low_stock' => 0, 'expiring' => 0, 'recipients' => 0];
        }

        $recipients = $this->recipients($tenant);

        foreach ($recipients as $user) {
            $branchIds = $this->branchContext->hasTenantWideBranchAccess($user)
                ? null
                : $user->branches()->pluck('branches.id')->all();

            $mine = fn (Collection $rows) => $branchIds === null ? $rows : $rows->filter(fn ($row) => in_array($row->branch_id, $branchIds, true));

            $userLowStock = $mine($lowStock);
            $userExpiring = $mine($expiring);

            if ($userLowStock->isEmpty() && $userExpiring->isEmpty()) {
                continue;
            }

            $user->notify(new InventoryAlertNotification(
                $tenant,
                $userLowStock->map(fn (Inventory $stock) => [
                    'product' => $stock->product->name,
                    'branch' => $stock->branch->name,
                    'on_hand' => (int) $stock->quantity_on_hand,
                    'reorder_level' => (int) $stock->product->reorder_level,
                ])->values(),
                $userExpiring->map(fn (ProductBatch $batch) => [
                    'product' => $batch->product->name,
                    'branch' => $batch->branch->name,
                    'batch' => $batch->batch_number,
                    'expiry_date' => $batch->expiry_date->format('M d, Y'),
                    'quantity' => (int) $batch->quantity_remaining,
                    'expired' => $batch->isExpired(),
                ])->values(),
            ));
        }

        // Marked even when nobody could be emailed, so a salon without inventory users is not re-scanned forever.
        Inventory::withoutTenantScope()->whereIn('id', $lowStock->pluck('id'))->update(['low_stock_notified_at' => now()]);
        ProductBatch::withoutTenantScope()->whereIn('id', $expiring->pluck('id'))->update(['expiry_notified_at' => now()]);

        return ['low_stock' => $lowStock->count(), 'expiring' => $expiring->count(), 'recipients' => $recipients->count()];
    }

    public function pendingLowStock(Tenant $tenant): Collection
    {
        return Inventory::withoutTenantScope()
            ->select('inventories.*')
            ->join('products', 'products.id', '=', 'inventories.product_id')
            ->where('inventories.tenant_id', $tenant->id)
            ->whereNull('inventories.low_stock_notified_at')
            ->whereNull('products.deleted_at')
            ->where('products.is_active', true)
            ->where('products.track_inventory', true)
            ->where('products.reorder_level', '>', 0)
            ->whereColumn('inventories.quantity_on_hand', '<=', 'products.reorder_level')
            ->with(['product', 'branch'])
            ->get();
    }

    public function pendingExpiring(Tenant $tenant): Collection
    {
        return ProductBatch::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->whereNull('expiry_notified_at')
            ->inStock()
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', today()->addDays(self::EXPIRY_WARNING_DAYS))
            ->with(['product', 'branch'])
            ->orderBy('expiry_date')
            ->get();
    }

    private function recipients(Tenant $tenant): Collection
    {
        return User::query()
            ->where('tenant_id', $tenant->id)
            ->where(fn ($query) => $query->whereNull('status')->orWhere('status', 'active'))
            ->whereNotNull('email')
            ->get()
            ->filter(fn (User $user) => $user->can('inventory.view'))
            ->values();
    }
}
