<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreVendorRequest;
use App\Http\Requests\Expense\UpdateVendorRequest;
use App\Models\Tenant;
use App\Models\Vendor;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function index(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', Vendor::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'expenses');
        }

        $vendors = ($isSuperAdmin ? Vendor::withoutTenantScope() : Vendor::query()->where('tenant_id', $tenant->id))
            ->with('tenant')
            ->withCount('expenses')
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(fn (Builder $query) => $query->where('name', 'like', "%{$search}%")->orWhere('company_name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.expense-management.vendors.index', [
            'vendors' => $vendors,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Vendor::class);

        return view('pages/apps.expense-management.vendors.create', [
            'vendor' => new Vendor(['is_active' => true]),
            'tenants' => $request->user()->hasRole('Super Admin') ? Tenant::query()->orderBy('name')->get() : collect(),
        ]);
    }

    public function store(StoreVendorRequest $request, PlanEntitlementService $entitlements): RedirectResponse
    {
        $tenant = $this->tenantForWrite($request);
        $entitlements->ensureFeature($tenant, 'expenses');

        Vendor::create($request->validated() + ['tenant_id' => $tenant->id]);

        return redirect()->route('expense-management.vendors.index')->with('status', 'Vendor created successfully.');
    }

    public function edit(Vendor $vendor): View
    {
        $this->authorize('update', $vendor);

        return view('pages/apps.expense-management.vendors.edit', [
            'vendor' => $vendor,
            'tenants' => collect(),
        ]);
    }

    public function update(UpdateVendorRequest $request, Vendor $vendor, PlanEntitlementService $entitlements): RedirectResponse
    {
        $entitlements->ensureFeature($vendor->tenant, 'expenses');
        $vendor->update($request->validated());

        return redirect()->route('expense-management.vendors.index')->with('status', 'Vendor updated successfully.');
    }

    public function destroy(Vendor $vendor): RedirectResponse
    {
        $this->authorize('delete', $vendor);
        $vendor->update(['is_active' => false]);
        $vendor->delete();

        return back()->with('status', 'Vendor archived successfully.');
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
        return $request->user()->hasRole('Super Admin') ? Tenant::query()->findOrFail($request->integer('tenant_id')) : $request->user()->tenant;
    }
}
