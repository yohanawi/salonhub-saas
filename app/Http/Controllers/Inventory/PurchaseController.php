<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventory\Concerns\ResolvesInventoryTenant;
use App\Http\Requests\Inventory\ReceivePurchaseRequest;
use App\Http\Requests\Inventory\StorePurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Services\Inventory\PurchaseService;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    use ResolvesInventoryTenant;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Purchase::class);
        [$isSuperAdmin, $tenant] = $this->inventoryTenant($request);
        $branches = $this->visibleBranches($request, $tenant);

        $purchases = Purchase::withoutTenantScope()
            ->with(['tenant', 'branch', 'supplier'])
            ->withCount('items')
            ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))
            ->when(! $this->hasTenantWideAccess($request), fn (Builder $query) => $query->whereIn('branch_id', $branches->pluck('id')))
            ->when($request->filled('branch_id'), fn (Builder $query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('supplier_id'), fn (Builder $query) => $query->where('supplier_id', $request->integer('supplier_id')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(fn (Builder $query) => $query->where('purchase_number', 'like', "%{$search}%")
                    ->orWhere('invoice_number', 'like', "%{$search}%"));
            })
            ->latest('purchase_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.inventory.purchases.index', [
            'purchases' => $purchases,
            'branches' => $branches,
            'suppliers' => $tenant ? Supplier::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('name')->get() : collect(),
            'statuses' => Purchase::STATUSES,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Purchase::class);
        [$isSuperAdmin, $tenant] = $this->inventoryTenant($request);

        $purchase = new Purchase([
            'purchase_date' => today(),
            'supplier_id' => $request->integer('supplier_id') ?: null,
        ]);

        return view('pages/apps.inventory.purchases.create', $this->formData($request, $tenant, $isSuperAdmin) + [
            'purchase' => $purchase,
            'itemRows' => old('items', $this->reorderSuggestions($request, $tenant)),
        ]);
    }

    public function store(StorePurchaseRequest $request, PurchaseService $purchases): RedirectResponse
    {
        $tenant = $request->user()->hasRole('Super Admin') ? null : $request->user()->tenant;
        $branch = $this->writableBranch($request, $request->integer('branch_id'), $tenant);

        $purchase = $purchases->create($branch, $request->validated(), $request->user());

        return redirect()
            ->route('inventory.purchases.show', $purchase)
            ->with('status', "Purchase order {$purchase->purchase_number} saved as draft.");
    }

    public function show(Purchase $purchase, PlanEntitlementService $entitlements): View
    {
        $this->authorize('view', $purchase);
        $entitlements->ensureFeature($purchase->tenant, 'inventory');

        $purchase->load(['branch', 'supplier', 'creator', 'items.product.unit', 'receipts.items.product', 'receipts.receivedBy']);

        return view('pages/apps.inventory.purchases.show', [
            'purchase' => $purchase,
        ]);
    }

    public function print(Purchase $purchase, PlanEntitlementService $entitlements): View
    {
        $this->authorize('view', $purchase);
        $entitlements->ensureFeature($purchase->tenant, 'inventory');

        $purchase->load(['tenant', 'branch', 'supplier', 'items.product.unit']);

        return view('pages/apps.inventory.purchases.print', [
            'purchase' => $purchase,
        ]);
    }

    public function edit(Request $request, Purchase $purchase, PlanEntitlementService $entitlements): View
    {
        $this->authorize('update', $purchase);
        $entitlements->ensureFeature($purchase->tenant, 'inventory');

        $purchase->load('items');

        return view('pages/apps.inventory.purchases.edit', $this->formData($request, $purchase->tenant, false) + [
            'purchase' => $purchase,
            'itemRows' => old('items', $purchase->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_cost' => $item->unit_cost,
            ])->all()),
        ]);
    }

    public function update(StorePurchaseRequest $request, Purchase $purchase, PurchaseService $purchases): RedirectResponse
    {
        $branch = $this->writableBranch($request, $request->integer('branch_id'), $purchase->tenant);

        $purchases->update($purchase, $branch, $request->validated());

        return redirect()
            ->route('inventory.purchases.show', $purchase)
            ->with('status', 'Purchase order updated.');
    }

    public function order(Purchase $purchase, PurchaseService $purchases): RedirectResponse
    {
        $this->authorize('order', $purchase);

        $purchases->markOrdered($purchase);

        return back()->with('status', "Purchase order {$purchase->purchase_number} marked as ordered.");
    }

    public function cancel(Purchase $purchase, PurchaseService $purchases): RedirectResponse
    {
        $this->authorize('cancel', $purchase);

        $purchases->cancel($purchase);

        return back()->with('status', "Purchase order {$purchase->purchase_number} cancelled.");
    }

    public function receive(ReceivePurchaseRequest $request, Purchase $purchase, PurchaseService $purchases, PlanEntitlementService $entitlements): RedirectResponse
    {
        $entitlements->ensureFeature($purchase->tenant, 'inventory');
        $data = $request->validated();

        $receipt = $purchases->receive($purchase, $data['items'], $request->user(), $data);

        return redirect()
            ->route('inventory.purchases.show', $purchase)
            ->with('status', "Goods received ({$receipt->receipt_number}). Stock has been updated.");
    }

    private function formData(Request $request, ?Tenant $tenant, bool $isSuperAdmin): array
    {
        return [
            'branches' => $this->visibleBranches($request, $tenant),
            'suppliers' => $tenant
                ? Supplier::withoutTenantScope()->where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('name')->get()
                : collect(),
            'products' => $tenant
                ? Product::withoutTenantScope()
                    ->with('unit:id,symbol')
                    ->where('tenant_id', $tenant->id)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'sku', 'unit_id', 'cost_price', 'track_batches'])
                : collect(),
            'isSuperAdmin' => $isSuperAdmin,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'selectedTenant' => $tenant,
        ];
    }

    /**
     * "Reorder low stock" pre-fills the order with every product at or under its reorder level at the branch.
     */
    private function reorderSuggestions(Request $request, ?Tenant $tenant): array
    {
        if (! $tenant || ! $request->boolean('reorder') || ! $request->filled('branch_id')
            || ! $this->visibleBranches($request, $tenant)->contains('id', $request->integer('branch_id'))) {
            return [];
        }

        return Product::withoutTenantScope()
            ->where('products.tenant_id', $tenant->id)
            ->where('products.is_active', true)
            ->where('products.track_inventory', true)
            ->where('products.reorder_level', '>', 0)
            ->leftJoin('inventories', function ($join) use ($request) {
                $join->on('inventories.product_id', '=', 'products.id')
                    ->where('inventories.branch_id', $request->integer('branch_id'));
            })
            ->whereRaw('COALESCE(inventories.quantity_on_hand, 0) <= products.reorder_level')
            ->orderBy('products.name')
            ->get(['products.id', 'products.cost_price', 'products.reorder_level', 'products.minimum_stock_level', 'inventories.quantity_on_hand'])
            ->map(fn ($product) => [
                'product_id' => $product->id,
                // Bring stock back up to twice the reorder level (at least one unit).
                'quantity' => max(1, (int) $product->reorder_level * 2 - (int) $product->quantity_on_hand),
                'unit_cost' => $product->cost_price,
            ])
            ->all();
    }
}
