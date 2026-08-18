<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Services\BranchContext;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function index(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', StockMovement::class);

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

        $movements = StockMovement::withoutTenantScope()
            ->with(['tenant', 'branch', 'product', 'creator'])
            ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))
            ->when(! $isSuperAdmin && ! $branchContext->hasTenantWideBranchAccess($request->user()), fn (Builder $query) => $query->whereIn('branch_id', $branches->pluck('id')))
            ->when($request->filled('branch_id'), fn (Builder $query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('product_id'), fn (Builder $query) => $query->where('product_id', $request->integer('product_id')))
            ->when($request->filled('type'), fn (Builder $query) => $query->where('type', $request->string('type')->toString()))
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('created_at', '<=', $request->date('date_to')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('pages/apps.inventory.movements.index', [
            'movements' => $movements,
            'branches' => $branches,
            'products' => ($tenant ? Product::withoutTenantScope()->where('tenant_id', $tenant->id) : Product::withoutTenantScope())->orderBy('name')->get(),
            'types' => StockMovement::TYPES,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }
}
