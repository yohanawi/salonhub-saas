<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreProductBrandRequest;
use App\Http\Requests\Inventory\UpdateProductBrandRequest;
use App\Models\ProductBrand;
use App\Models\Tenant;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductBrandController extends Controller
{
    public function index(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', ProductBrand::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'inventory');
        }

        $brands = ($isSuperAdmin ? ProductBrand::withoutTenantScope() : ProductBrand::query()->where('tenant_id', $tenant->id))
            ->with('tenant')
            ->withCount('products')
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when($request->filled('search'), fn (Builder $query) => $query->where('name', 'like', '%' . $request->string('search')->toString() . '%'))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('is_active', $request->string('status')->toString() === 'active'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.inventory.brands.index', [
            'brands' => $brands,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', ProductBrand::class);

        return view('pages/apps.inventory.brands.create', [
            'brand' => new ProductBrand(['is_active' => true]),
            'tenants' => $request->user()->hasRole('Super Admin') ? Tenant::query()->orderBy('name')->get() : collect(),
        ]);
    }

    public function store(StoreProductBrandRequest $request, PlanEntitlementService $entitlements): RedirectResponse
    {
        $tenant = $this->tenantForWrite($request);
        $entitlements->ensureFeature($tenant, 'inventory');
        $data = $request->validated();

        ProductBrand::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return redirect()->route('inventory.brands.index')->with('status', 'Product brand created successfully.');
    }

    public function edit(ProductBrand $brand): View
    {
        $this->authorize('update', $brand);

        return view('pages/apps.inventory.brands.edit', [
            'brand' => $brand,
            'tenants' => collect(),
        ]);
    }

    public function update(UpdateProductBrandRequest $request, ProductBrand $brand, PlanEntitlementService $entitlements): RedirectResponse
    {
        $entitlements->ensureFeature($brand->tenant, 'inventory');
        $data = $request->validated();

        $brand->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return redirect()->route('inventory.brands.index')->with('status', 'Product brand updated successfully.');
    }

    public function destroy(ProductBrand $brand): RedirectResponse
    {
        $this->authorize('delete', $brand);
        $brand->update(['is_active' => false]);

        return back()->with('status', 'Product brand deactivated successfully.');
    }

    private function tenantContext(Request $request): array
    {
        $isSuperAdmin = $request->user()->hasRole('Super Admin');

        return [
            $isSuperAdmin,
            $isSuperAdmin && $request->filled('tenant_id') ? Tenant::query()->findOrFail($request->integer('tenant_id')) : ($isSuperAdmin ? null : $request->user()->tenant),
        ];
    }

    private function tenantForWrite(Request $request): Tenant
    {
        return $request->user()->hasRole('Super Admin')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : $request->user()->tenant;
    }
}
