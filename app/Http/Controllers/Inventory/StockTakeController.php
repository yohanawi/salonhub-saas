<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventory\Concerns\ResolvesInventoryTenant;
use App\Models\ProductCategory;
use App\Models\StockTake;
use App\Services\Inventory\StockTakeService;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockTakeController extends Controller
{
    use ResolvesInventoryTenant;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', StockTake::class);
        [$isSuperAdmin, $tenant] = $this->inventoryTenant($request);
        $branches = $this->visibleBranches($request, $tenant);

        $stockTakes = StockTake::withoutTenantScope()
            ->with(['tenant', 'branch', 'category', 'createdBy'])
            ->withCount([
                'items',
                'items as counted_count' => fn (Builder $query) => $query->whereNotNull('counted_quantity'),
                'items as variance_count' => fn (Builder $query) => $query->whereNotNull('variance')->where('variance', '!=', 0),
            ])
            ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))
            ->when(! $this->hasTenantWideAccess($request), fn (Builder $query) => $query->whereIn('branch_id', $branches->pluck('id')))
            ->when($request->filled('branch_id'), fn (Builder $query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.inventory.stock-takes.index', [
            'stockTakes' => $stockTakes,
            'branches' => $branches,
            'categories' => $tenant ? ProductCategory::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function store(Request $request, StockTakeService $stockTakes): RedirectResponse
    {
        $this->authorize('create', StockTake::class);

        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $tenant = $request->user()->hasRole('Super Admin') ? null : $request->user()->tenant;
        $branch = $this->writableBranch($request, (int) $data['branch_id'], $tenant);

        $categoryId = ! empty($data['category_id'])
            ? ProductCategory::withoutTenantScope()->where('tenant_id', $branch->tenant_id)->findOrFail($data['category_id'])->id
            : null;

        $stockTake = $stockTakes->start($branch, $categoryId, $data['notes'] ?? null, $request->user());

        return redirect()
            ->route('inventory.stock-takes.show', $stockTake)
            ->with('status', "Stock take {$stockTake->stock_take_number} started. Enter the counted quantities below.");
    }

    public function show(StockTake $stockTake, PlanEntitlementService $entitlements): View
    {
        $this->authorize('view', $stockTake);
        $entitlements->ensureFeature($stockTake->tenant, 'inventory');

        $stockTake->load(['branch', 'category', 'createdBy', 'completedBy', 'adjustment', 'items' => fn ($query) => $query->with('product.unit', 'product.category')]);
        $stockTake->setRelation('items', $stockTake->items->sortBy(fn ($item) => $item->product?->name)->values());

        return view('pages/apps.inventory.stock-takes.show', [
            'stockTake' => $stockTake,
        ]);
    }

    public function print(StockTake $stockTake, PlanEntitlementService $entitlements): View
    {
        $this->authorize('view', $stockTake);
        $entitlements->ensureFeature($stockTake->tenant, 'inventory');

        $stockTake->load(['tenant', 'branch', 'category', 'items.product.unit', 'items.product.category']);
        $stockTake->setRelation('items', $stockTake->items->sortBy(fn ($item) => $item->product?->name)->values());

        return view('pages/apps.inventory.stock-takes.print', [
            'stockTake' => $stockTake,
            // Blind sheets hide the system quantity so counters are not anchored to it.
            'blind' => request()->boolean('blind', true),
        ]);
    }

    public function update(Request $request, StockTake $stockTake, StockTakeService $stockTakes): RedirectResponse
    {
        $this->authorize('update', $stockTake);

        $data = $request->validate([
            'counts' => ['nullable', 'array'],
            'counts.*' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'action' => ['nullable', Rule::in(['save', 'complete'])],
        ]);

        $stockTakes->saveCounts($stockTake, $data['counts'] ?? []);

        if (($data['action'] ?? 'save') === 'complete') {
            $stockTakes->complete($stockTake->fresh(), $request->user());

            return redirect()
                ->route('inventory.stock-takes.show', $stockTake)
                ->with('status', 'Stock take completed. Differences have been posted as a stock adjustment.');
        }

        return back()->with('status', 'Counts saved.');
    }

    public function cancel(StockTake $stockTake, StockTakeService $stockTakes): RedirectResponse
    {
        $this->authorize('update', $stockTake);

        $stockTakes->cancel($stockTake);

        return back()->with('status', 'Stock take cancelled. No stock was changed.');
    }
}
