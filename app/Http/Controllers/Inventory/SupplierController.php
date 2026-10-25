<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventory\Concerns\ResolvesInventoryTenant;
use App\Http\Requests\Inventory\StoreSupplierRequest;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    use ResolvesInventoryTenant;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Supplier::class);
        [$isSuperAdmin, $tenant] = $this->inventoryTenant($request);

        $suppliers = Supplier::withoutTenantScope()
            ->with('tenant')
            ->withCount(['purchases as open_orders_count' => fn (Builder $query) => $query->whereIn('status', Purchase::RECEIVABLE_STATUSES)])
            ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function (Builder $query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn (Builder $query) => $query->where('is_active', $request->string('status')->toString() === 'active'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.inventory.suppliers.index', [
            'suppliers' => $suppliers,
            'isSuperAdmin' => $isSuperAdmin,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Supplier::class);
        [$isSuperAdmin, $tenant] = $this->inventoryTenant($request);

        return view('pages/apps.inventory.suppliers.create', [
            'supplier' => new Supplier(['is_active' => true]),
            'isSuperAdmin' => $isSuperAdmin,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'selectedTenant' => $tenant,
        ]);
    }

    public function store(StoreSupplierRequest $request, PlanEntitlementService $entitlements): RedirectResponse
    {
        $tenant = $request->user()->hasRole('Super Admin')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : $request->user()->tenant;

        $entitlements->ensureFeature($tenant, 'inventory');

        Supplier::withoutTenantScope()->create($this->payload($request) + [
            'tenant_id' => $tenant->id,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('inventory.suppliers.index')->with('status', 'Supplier created successfully.');
    }

    public function edit(Supplier $supplier, PlanEntitlementService $entitlements): View
    {
        $this->authorize('update', $supplier);
        $entitlements->ensureFeature($supplier->tenant, 'inventory');

        return view('pages/apps.inventory.suppliers.edit', [
            'supplier' => $supplier,
            'isSuperAdmin' => false,
            'tenants' => collect(),
            'selectedTenant' => $supplier->tenant,
        ]);
    }

    public function update(StoreSupplierRequest $request, Supplier $supplier, PlanEntitlementService $entitlements): RedirectResponse
    {
        $entitlements->ensureFeature($supplier->tenant, 'inventory');

        $supplier->update($this->payload($request));

        return redirect()->route('inventory.suppliers.index')->with('status', 'Supplier updated successfully.');
    }

    private function payload(StoreSupplierRequest $request): array
    {
        $data = $request->validated();

        return [
            'name' => $data['name'],
            'contact_person' => $data['contact_person'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'tax_number' => $data['tax_number'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }
}
