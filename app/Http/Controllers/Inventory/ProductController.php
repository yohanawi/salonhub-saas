<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreProductRequest;
use App\Http\Requests\Inventory\UpdateProductRequest;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\Unit;
use App\Services\BranchContext;
use App\Services\Inventory\InventoryService;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', Product::class);

        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'inventory');
        }

        $branches = $this->branches($request, $branchContext, $tenant, $isSuperAdmin);

        $products = ($isSuperAdmin ? Product::withoutTenantScope() : Product::query()->where('tenant_id', $tenant->id))
            ->with(['tenant', 'category', 'brand', 'unit', 'inventories.branch'])
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function (Builder $query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category_id'), fn (Builder $query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->filled('brand_id'), fn (Builder $query) => $query->where('brand_id', $request->integer('brand_id')))
            ->when($request->filled('product_type'), fn (Builder $query) => $query->where('product_type', $request->string('product_type')->toString()))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('is_active', $request->string('status')->toString() === 'active'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.inventory.products.index', [
            'products' => $products,
            'categories' => $this->categories($tenant, $isSuperAdmin, $request),
            'brands' => $this->brands($tenant, $isSuperAdmin, $request),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
            'productTypes' => Product::TYPES,
            'branches' => $branches,
        ]);
    }

    public function create(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('create', Product::class);

        [$isSuperAdmin, $tenant] = $this->tenantContext($request, true);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'inventory');
        }

        return view('pages/apps.inventory.products.create', [
            'product' => new Product([
                'product_type' => Product::TYPE_RETAIL,
                'track_inventory' => true,
                'is_active' => true,
                'cost_price' => 0,
                'selling_price' => 0,
                'tax_rate' => 0,
                'reorder_level' => 0,
                'minimum_stock_level' => 0,
            ]),
            'categories' => $this->categories($tenant, $isSuperAdmin, $request, true),
            'brands' => $this->brands($tenant, $isSuperAdmin, $request, true),
            'units' => $this->units($tenant, $isSuperAdmin, $request, true),
            'branches' => $this->branches($request, $branchContext, $tenant, $isSuperAdmin),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'selectedTenant' => $tenant,
            'productTypes' => Product::TYPES,
        ]);
    }

    public function store(StoreProductRequest $request, InventoryService $inventory, PlanEntitlementService $entitlements): RedirectResponse
    {
        $tenant = $this->tenantForWrite($request);
        $entitlements->ensureFeature($tenant, 'inventory');
        $data = $this->validatedProductData($request->validated(), $tenant->id, $request->user()->id);

        $product = Product::create($data);

        if ((int) ($request->input('opening_quantity') ?? 0) > 0 && $request->filled('opening_branch_id')) {
            $branch = Branch::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->findOrFail($request->integer('opening_branch_id'));

            $inventory->openingStock($product, $branch, $request->integer('opening_quantity'), (float) ($request->input('opening_cost') ?: $product->cost_price), $request->user());
        }

        return redirect()
            ->route('inventory.products.show', $product)
            ->with('status', 'Product created successfully.');
    }

    public function show(Request $request, Product $product, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('view', $product);
        $entitlements->ensureFeature($product->tenant, 'inventory');

        $product->load(['tenant', 'category', 'brand', 'unit', 'inventories.branch', 'stockMovements.branch', 'stockMovements.creator']);

        return view('pages/apps.inventory.products.show', [
            'product' => $product,
            'branches' => $this->branches($request, $branchContext, $product->tenant, $request->user()->hasRole('Super Admin')),
        ]);
    }

    public function edit(Request $request, Product $product, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('update', $product);
        $entitlements->ensureFeature($product->tenant, 'inventory');

        return view('pages/apps.inventory.products.edit', [
            'product' => $product,
            'categories' => $this->categories($product->tenant, false, $request, true),
            'brands' => $this->brands($product->tenant, false, $request, true),
            'units' => $this->units($product->tenant, false, $request, true),
            'branches' => $this->branches($request, $branchContext, $product->tenant, $request->user()->hasRole('Super Admin')),
            'tenants' => collect(),
            'selectedTenant' => $product->tenant,
            'productTypes' => Product::TYPES,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product, PlanEntitlementService $entitlements): RedirectResponse
    {
        $entitlements->ensureFeature($product->tenant, 'inventory');

        $product->update($this->validatedProductData($request->validated(), $product->tenant_id, $request->user()->id, true));

        return redirect()
            ->route('inventory.products.show', $product)
            ->with('status', 'Product updated successfully.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $product->update(['is_active' => false, 'updated_by' => $request->user()->id]);
        $product->delete();

        return redirect()
            ->route('inventory.products.index')
            ->with('status', 'Product deactivated and archived successfully.');
    }

    private function validatedProductData(array $data, int $tenantId, int $userId, bool $isUpdate = false): array
    {
        $type = $data['product_type'];

        return [
            'tenant_id' => $tenantId,
            'category_id' => $data['category_id'] ?? null,
            'brand_id' => $data['brand_id'] ?? null,
            'unit_id' => $data['unit_id'] ?? null,
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'sku' => $data['sku'],
            'barcode' => $data['barcode'] ?? null,
            'description' => $data['description'] ?? null,
            'product_type' => $type,
            'cost_price' => $data['cost_price'],
            'selling_price' => $data['selling_price'],
            'tax_rate' => $data['tax_rate'] ?? 0,
            'track_inventory' => (bool) ($data['track_inventory'] ?? false),
            'minimum_stock_level' => $data['minimum_stock_level'] ?? 0,
            'reorder_level' => $data['reorder_level'] ?? 0,
            'allow_negative_stock' => (bool) ($data['allow_negative_stock'] ?? false),
            'is_sellable' => in_array($type, [Product::TYPE_RETAIL, Product::TYPE_BOTH], true),
            'is_consumable' => in_array($type, [Product::TYPE_CONSUMABLE, Product::TYPE_BOTH], true),
            'is_active' => (bool) ($data['is_active'] ?? false),
            $isUpdate ? 'updated_by' : 'created_by' => $userId,
        ];
    }

    private function tenantContext(Request $request, bool $forCreate = false): array
    {
        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : ($isSuperAdmin ? null : $request->user()->tenant);

        if ($forCreate && ! $tenant && ! $isSuperAdmin) {
            abort(403);
        }

        return [$isSuperAdmin, $tenant];
    }

    private function tenantForWrite(Request $request): Tenant
    {
        return $request->user()->hasRole('Super Admin')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : $request->user()->tenant;
    }

    private function categories(?Tenant $tenant, bool $isSuperAdmin, Request $request, bool $activeOnly = false)
    {
        return ($isSuperAdmin && ! $tenant ? ProductCategory::withoutTenantScope() : ProductCategory::withoutTenantScope()->where('tenant_id', $tenant->id))
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when($activeOnly, fn (Builder $query) => $query->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function brands(?Tenant $tenant, bool $isSuperAdmin, Request $request, bool $activeOnly = false)
    {
        return ($isSuperAdmin && ! $tenant ? ProductBrand::withoutTenantScope() : ProductBrand::withoutTenantScope()->where('tenant_id', $tenant->id))
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when($activeOnly, fn (Builder $query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get();
    }

    private function units(?Tenant $tenant, bool $isSuperAdmin, Request $request, bool $activeOnly = false)
    {
        return ($isSuperAdmin && ! $tenant ? Unit::withoutTenantScope() : Unit::withoutTenantScope()->where('tenant_id', $tenant->id))
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when($activeOnly, fn (Builder $query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get();
    }

    private function branches(Request $request, BranchContext $branchContext, ?Tenant $tenant, bool $isSuperAdmin)
    {
        if ($isSuperAdmin) {
            return Branch::withoutTenantScope()
                ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))
                ->orderBy('name')
                ->get();
        }

        return $branchContext->availableBranches($request->user());
    }
}
