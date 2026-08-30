<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Services\BranchContext;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CurrentStockController extends Controller
{
    public function index(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        abort_unless($request->user()->hasRole('Super Admin') || $request->user()->can('stock.view'), 403);

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

        $stocks = Inventory::withoutTenantScope()
            ->with(['tenant', 'branch', 'product.category', 'product.brand', 'product.unit'])
            ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when(! $isSuperAdmin && ! $branchContext->hasTenantWideBranchAccess($request->user()), fn (Builder $query) => $query->whereIn('branch_id', $branches->pluck('id')))
            ->when($request->filled('branch_id'), fn (Builder $query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('category_id'), fn (Builder $query) => $query->whereHas('product', fn (Builder $query) => $query->where('category_id', $request->integer('category_id'))))
            ->when($request->filled('brand_id'), fn (Builder $query) => $query->whereHas('product', fn (Builder $query) => $query->where('brand_id', $request->integer('brand_id'))))
            ->when($request->filled('product_type'), fn (Builder $query) => $query->whereHas('product', fn (Builder $query) => $query->where('product_type', $request->string('product_type')->toString())))
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->string('search')->toString();

                $query->whereHas('product', function (Builder $query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        if ($request->filled('stock_status')) {
            $filtered = $stocks->getCollection()->filter(fn (Inventory $stock) => $stock->stock_status === $request->string('stock_status')->toString())->values();
            $stocks->setCollection($filtered);
        }

        return view('pages/apps.inventory.stock.index', [
            'stocks' => $stocks,
            'branches' => $branches,
            'categories' => $tenant || ! $isSuperAdmin ? ProductCategory::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))->orderBy('name')->get() : ProductCategory::withoutTenantScope()->orderBy('name')->get(),
            'brands' => $tenant || ! $isSuperAdmin ? ProductBrand::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))->orderBy('name')->get() : ProductBrand::withoutTenantScope()->orderBy('name')->get(),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }
}
