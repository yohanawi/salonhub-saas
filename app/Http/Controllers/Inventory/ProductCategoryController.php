<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreProductCategoryRequest;
use App\Http\Requests\Inventory\UpdateProductCategoryRequest;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductCategoryController extends Controller
{
    public function index(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', ProductCategory::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'inventory');
        }

        $categories = ($isSuperAdmin ? ProductCategory::withoutTenantScope() : ProductCategory::query()->where('tenant_id', $tenant->id))
            ->with('tenant')
            ->withCount('products')
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when($request->filled('search'), fn (Builder $query) => $query->where('name', 'like', '%' . $request->string('search')->toString() . '%'))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('is_active', $request->string('status')->toString() === 'active'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.inventory.categories.index', [
            'categories' => $categories,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', ProductCategory::class);

        return view('pages/apps.inventory.categories.create', [
            'category' => new ProductCategory(['is_active' => true, 'sort_order' => 0]),
            'tenants' => $request->user()->hasRole('Super Admin') ? Tenant::query()->orderBy('name')->get() : collect(),
        ]);
    }

    public function store(StoreProductCategoryRequest $request, PlanEntitlementService $entitlements): RedirectResponse
    {
        $tenant = $this->tenantForWrite($request);
        $entitlements->ensureFeature($tenant, 'inventory');
        $data = $request->validated();

        ProductCategory::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return redirect()->route('inventory.categories.index')->with('status', 'Product category created successfully.');
    }

    public function edit(ProductCategory $category): View
    {
        $this->authorize('update', $category);

        return view('pages/apps.inventory.categories.edit', [
            'category' => $category,
            'tenants' => collect(),
        ]);
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $category, PlanEntitlementService $entitlements): RedirectResponse
    {
        $entitlements->ensureFeature($category->tenant, 'inventory');
        $data = $request->validated();

        $category->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return redirect()->route('inventory.categories.index')->with('status', 'Product category updated successfully.');
    }

    public function destroy(ProductCategory $category): RedirectResponse
    {
        $this->authorize('delete', $category);
        $category->update(['is_active' => false]);

        return back()->with('status', 'Product category deactivated successfully.');
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
