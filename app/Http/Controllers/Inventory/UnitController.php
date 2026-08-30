<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreUnitRequest;
use App\Http\Requests\Inventory\UpdateUnitRequest;
use App\Models\Tenant;
use App\Models\Unit;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function index(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', Unit::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'inventory');
        }

        $units = ($isSuperAdmin ? Unit::withoutTenantScope() : Unit::query()->where('tenant_id', $tenant->id))
            ->with('tenant')
            ->withCount('products')
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function (Builder $query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('symbol', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.inventory.units.index', [
            'units' => $units,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
            'types' => Unit::TYPES,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Unit::class);

        return view('pages/apps.inventory.units.create', [
            'unit' => new Unit(['type' => Unit::TYPE_PIECE, 'is_active' => true]),
            'tenants' => $request->user()->hasRole('Super Admin') ? Tenant::query()->orderBy('name')->get() : collect(),
            'types' => Unit::TYPES,
        ]);
    }

    public function store(StoreUnitRequest $request, PlanEntitlementService $entitlements): RedirectResponse
    {
        $tenant = $this->tenantForWrite($request);
        $entitlements->ensureFeature($tenant, 'inventory');
        $data = $request->validated();

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'symbol' => $data['symbol'],
            'type' => $data['type'],
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return redirect()->route('inventory.units.index')->with('status', 'Unit created successfully.');
    }

    public function edit(Unit $unit): View
    {
        $this->authorize('update', $unit);

        return view('pages/apps.inventory.units.edit', [
            'unit' => $unit,
            'tenants' => collect(),
            'types' => Unit::TYPES,
        ]);
    }

    public function update(UpdateUnitRequest $request, Unit $unit, PlanEntitlementService $entitlements): RedirectResponse
    {
        $entitlements->ensureFeature($unit->tenant, 'inventory');
        $unit->update($request->validated());

        return redirect()->route('inventory.units.index')->with('status', 'Unit updated successfully.');
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
