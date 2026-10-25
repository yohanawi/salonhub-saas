<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventory\Concerns\ResolvesInventoryTenant;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Services\Inventory\StockTransferService;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockTransferController extends Controller
{
    use ResolvesInventoryTenant;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', StockTransfer::class);
        [$isSuperAdmin, $tenant] = $this->inventoryTenant($request);
        $branches = $this->visibleBranches($request, $tenant);
        $branchIds = $branches->pluck('id');

        $transfers = StockTransfer::withoutTenantScope()
            ->with(['fromBranch', 'toBranch', 'createdBy'])
            ->withCount('items')
            ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))
            ->when(! $this->hasTenantWideAccess($request), fn (Builder $query) => $query->where(fn (Builder $query) => $query->whereIn('from_branch_id', $branchIds)->orWhereIn('to_branch_id', $branchIds)))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.inventory.transfers.index', [
            'transfers' => $transfers,
            'branches' => $branches,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', StockTransfer::class);
        [, $tenant] = $this->inventoryTenant($request);
        abort_unless($tenant, 403);

        return view('pages/apps.inventory.transfers.create', [
            'fromBranches' => $this->visibleBranches($request, $tenant),
            'toBranches' => Branch::withoutTenantScope()->where('tenant_id', $tenant->id)->where('status', Branch::STATUS_ACTIVE)->orderBy('name')->get(),
            'products' => Product::withoutTenantScope()->with('unit:id,symbol')->where('tenant_id', $tenant->id)->where('track_inventory', true)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'unit_id']),
            'stock' => Inventory::withoutTenantScope()->where('tenant_id', $tenant->id)->get(['branch_id', 'product_id', 'quantity_on_hand'])
                ->groupBy('branch_id')->map(fn ($rows) => $rows->pluck('quantity_on_hand', 'product_id')),
        ]);
    }

    public function store(Request $request, StockTransferService $transfers): RedirectResponse
    {
        $this->authorize('create', StockTransfer::class);

        $request->merge(['items' => collect($request->input('items', []))->filter(fn ($item) => filled($item['product_id'] ?? null))->values()->all()]);
        $data = $request->validate([
            'from_branch_id' => ['required', 'integer', 'exists:branches,id'],
            'to_branch_id' => ['required', 'integer', 'exists:branches,id', 'different:from_branch_id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'send_now' => ['nullable', 'boolean'],
        ], ['items.required' => 'Add at least one product to transfer.', 'to_branch_id.different' => 'Choose a different branch to send stock to.']);

        $tenant = $request->user()->hasRole('Super Admin') ? null : $request->user()->tenant;
        $from = $this->writableBranch($request, (int) $data['from_branch_id'], $tenant);
        $to = Branch::withoutTenantScope()->where('tenant_id', $from->tenant_id)->findOrFail($data['to_branch_id']);

        $transfer = $transfers->create($from, $to, $data['items'], $data['notes'] ?? null, $request->user());

        if (! empty($data['send_now'])) {
            $transfers->send($transfer, $request->user());
        }

        return redirect()->route('inventory.transfers.show', $transfer)->with('status', "Transfer {$transfer->transfer_number} " . (! empty($data['send_now']) ? 'sent. Stock is now in transit.' : 'saved as draft.'));
    }

    public function show(StockTransfer $transfer, PlanEntitlementService $entitlements): View
    {
        $this->authorize('view', $transfer);
        $entitlements->ensureFeature($transfer->tenant, 'inventory');

        $transfer->load(['fromBranch', 'toBranch', 'items.product.unit', 'createdBy', 'sentBy', 'receivedBy']);

        return view('pages/apps.inventory.transfers.show', ['transfer' => $transfer]);
    }

    public function send(StockTransfer $transfer, StockTransferService $transfers): RedirectResponse
    {
        $this->authorize('send', $transfer);
        $transfers->send($transfer, request()->user());

        return back()->with('status', 'Stock sent. It will show at the receiving branch once they confirm receipt.');
    }

    public function receive(Request $request, StockTransfer $transfer, StockTransferService $transfers): RedirectResponse
    {
        $this->authorize('receive', $transfer);
        $data = $request->validate(['received' => ['nullable', 'array'], 'received.*' => ['nullable', 'integer', 'min:0']]);

        $transfers->receive($transfer, $data['received'] ?? [], $request->user());

        return back()->with('status', 'Transfer received. Stock has been added to ' . $transfer->toBranch?->name . '.');
    }

    public function cancel(StockTransfer $transfer, StockTransferService $transfers): RedirectResponse
    {
        $this->authorize('cancel', $transfer);
        $transfers->cancel($transfer, request()->user());

        return back()->with('status', 'Transfer cancelled.');
    }
}
