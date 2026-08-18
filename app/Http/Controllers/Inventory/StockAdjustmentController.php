<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreStockAdjustmentRequest;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\Tenant;
use App\Services\BranchContext;
use App\Services\Inventory\InventoryService;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function index(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', StockAdjustment::class);

        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : ($isSuperAdmin ? null : $request->user()->tenant);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'inventory');
        }

        $branches = $isSuperAdmin
            ? Branch::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))->orderBy('name')->get()
            : $branchContext->availableBranches($request->user());

        $adjustments = StockAdjustment::withoutTenantScope()
            ->with(['tenant', 'branch', 'createdBy'])
            ->withCount('items')
            ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))
            ->when(! $isSuperAdmin && ! $branchContext->hasTenantWideBranchAccess($request->user()), fn (Builder $query) => $query->whereIn('branch_id', $branches->pluck('id')))
            ->when($request->filled('branch_id'), fn (Builder $query) => $query->where('branch_id', $request->integer('branch_id')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.inventory.adjustments.index', [
            'adjustments' => $adjustments,
            'branches' => $branches,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function create(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('create', StockAdjustment::class);

        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : ($isSuperAdmin ? null : $request->user()->tenant);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'inventory');
        }

        return view('pages/apps.inventory.adjustments.create', [
            'branches' => $isSuperAdmin
                ? Branch::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))->orderBy('name')->get()
                : $branchContext->availableBranches($request->user()),
            'products' => ($tenant ? Product::withoutTenantScope()->where('tenant_id', $tenant->id) : Product::withoutTenantScope())->where('track_inventory', true)->orderBy('name')->get(),
            'reasons' => StockAdjustment::REASONS,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'selectedTenant' => $tenant,
        ]);
    }

    public function store(StoreStockAdjustmentRequest $request, BranchContext $branchContext, InventoryService $inventory, PlanEntitlementService $entitlements): RedirectResponse
    {
        $branch = Branch::withoutTenantScope()->findOrFail($request->integer('branch_id'));

        abort_unless($request->user()->hasRole('Super Admin') || $branchContext->canAccess($request->user(), $branch), 403);

        $entitlements->ensureFeature($branch->tenant, 'inventory');
        $data = $request->validated();

        $adjustment = $inventory->adjustStock(
            $branch,
            $data['items'],
            $data['reason'],
            $data['notes'] ?? null,
            $request->user()
        );

        return redirect()
            ->route('inventory.adjustments.show', $adjustment)
            ->with('status', 'Stock adjustment posted successfully.');
    }

    public function show(StockAdjustment $adjustment, PlanEntitlementService $entitlements): View
    {
        $this->authorize('view', $adjustment);
        $entitlements->ensureFeature($adjustment->tenant, 'inventory');

        $adjustment->load(['branch', 'items.product.unit', 'createdBy', 'approvedBy']);

        return view('pages/apps.inventory.adjustments.show', [
            'adjustment' => $adjustment,
        ]);
    }
}
