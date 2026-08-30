<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Services\BranchContext;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryDashboardController extends Controller
{
    public function __invoke(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', Product::class);

        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : ($isSuperAdmin ? null : $request->user()->tenant);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'inventory');
        }

        $branches = $isSuperAdmin
            ? \App\Models\Branch::withoutTenantScope()->orderBy('name')->get()
            : $branchContext->availableBranches($request->user());

        $stockQuery = Inventory::withoutTenantScope()
            ->with(['product.category', 'branch'])
            ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when(! $isSuperAdmin && ! $branchContext->hasTenantWideBranchAccess($request->user()), fn (Builder $query) => $query->whereIn('branch_id', $branches->pluck('id')));

        $stocks = (clone $stockQuery)->get();
        $lowStock = $stocks->filter(fn (Inventory $stock) => $stock->stock_status === Inventory::STATUS_LOW_STOCK);
        $outOfStock = $stocks->filter(fn (Inventory $stock) => $stock->stock_status === Inventory::STATUS_OUT_OF_STOCK);

        $productsQuery = ($isSuperAdmin ? Product::withoutTenantScope() : Product::query()->where('tenant_id', $tenant->id))
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')));

        return view('pages/apps.inventory.dashboard', [
            'totalProducts' => (clone $productsQuery)->count(),
            'activeProducts' => (clone $productsQuery)->where('is_active', true)->count(),
            'inventoryValue' => $stocks->sum(fn (Inventory $stock) => (float) $stock->average_cost * (int) $stock->quantity_on_hand),
            'lowStockCount' => $lowStock->count(),
            'outOfStockCount' => $outOfStock->count(),
            'lowStockItems' => $lowStock->take(8),
            'recentMovements' => StockMovement::withoutTenantScope()
                ->with(['product', 'branch', 'creator'])
                ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))
                ->when(! $isSuperAdmin && ! $branchContext->hasTenantWideBranchAccess($request->user()), fn (Builder $query) => $query->whereIn('branch_id', $branches->pluck('id')))
                ->latest()
                ->take(8)
                ->get(),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }
}
