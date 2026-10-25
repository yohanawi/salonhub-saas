<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventory\Concerns\ResolvesInventoryTenant;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use App\Services\Inventory\InventoryService;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductBatchController extends Controller
{
    use ResolvesInventoryTenant;

    public const FILTERS = ['expiring', 'expired', 'in_stock', 'all'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ProductBatch::class);
        [$isSuperAdmin, $tenant] = $this->inventoryTenant($request);
        $branches = $this->visibleBranches($request, $tenant);

        $filter = in_array($request->query('filter'), self::FILTERS, true) ? $request->query('filter') : 'expiring';
        $days = max(1, min(365, $request->integer('days', 30)));

        $base = ProductBatch::withoutTenantScope()
            ->when($tenant, fn (Builder $query) => $query->where('product_batches.tenant_id', $tenant->id))
            ->when(! $this->hasTenantWideAccess($request), fn (Builder $query) => $query->whereIn('product_batches.branch_id', $branches->pluck('id')))
            ->when($request->filled('branch_id'), fn (Builder $query) => $query->where('product_batches.branch_id', $request->integer('branch_id')));

        $batches = (clone $base)
            ->with(['tenant', 'branch', 'product.unit'])
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(fn (Builder $query) => $query->where('batch_number', 'like', "%{$search}%")
                    ->orWhereHas('product', fn (Builder $product) => $product->where('name', 'like', "%{$search}%")));
            })
            ->when($filter === 'expiring', fn (Builder $query) => $query->inStock()
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '>=', today())
                ->whereDate('expiry_date', '<=', today()->addDays($days)))
            ->when($filter === 'expired', fn (Builder $query) => $query->inStock()->whereDate('expiry_date', '<', today()))
            ->when($filter === 'in_stock', fn (Builder $query) => $query->inStock())
            ->fefo()
            ->paginate(20)
            ->withQueryString();

        return view('pages/apps.inventory.batches.index', [
            'batches' => $batches,
            'branches' => $branches,
            'filter' => $filter,
            'days' => $days,
            'summary' => [
                'expired' => (clone $base)->inStock()->whereDate('expiry_date', '<', today())->count(),
                'expiring' => (clone $base)->inStock()->whereNotNull('expiry_date')->whereDate('expiry_date', '>=', today())->whereDate('expiry_date', '<=', today()->addDays($days))->count(),
                'in_stock' => (clone $base)->inStock()->count(),
            ],
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function writeOff(Request $request, ProductBatch $batch, InventoryService $inventory, PlanEntitlementService $entitlements): RedirectResponse
    {
        $this->authorize('writeOff', $batch);
        $entitlements->ensureFeature($batch->tenant, 'inventory');

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:' . max(1, (int) $batch->quantity_remaining)],
            'reason' => ['required', Rule::in([StockMovement::TYPE_EXPIRED, StockMovement::TYPE_DAMAGED])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $batch->loadMissing(['product', 'branch']);

        $inventory->decrease(
            $batch->product,
            $batch->branch,
            (int) $data['quantity'],
            $data['reason'],
            $request->user(),
            $batch,
            $data['reason'] === StockMovement::TYPE_EXPIRED ? 'Expired batch write-off' : 'Damaged batch write-off',
            trim("Batch {$batch->batch_number}. " . ($data['notes'] ?? '')),
            $batch
        );

        return back()->with('status', "{$data['quantity']} units of {$batch->product->name} (batch {$batch->batch_number}) written off.");
    }
}
