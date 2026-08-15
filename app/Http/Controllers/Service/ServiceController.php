<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Models\Branch;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Tenant;
use App\Services\BranchContext;
use App\Services\PlanEntitlementService;
use App\Services\ServiceCatalogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', Service::class);

        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin ? null : $this->tenant($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'services');
        }

        $visibleBranches = $isSuperAdmin
            ? Branch::withoutTenantScope()->orderBy('name')->get()
            : $branchContext->availableBranches($request->user());
        $hasTenantWideAccess = $isSuperAdmin || $branchContext->hasTenantWideBranchAccess($request->user());

        $services = ($isSuperAdmin ? Service::withoutTenantScope() : Service::query()->where('tenant_id', $tenant->id))
            ->with(['tenant', 'category', 'branches'])
            ->withCount('branches')
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when(! $hasTenantWideAccess, function (Builder $query) use ($visibleBranches) {
                $query->whereHas('branches', fn (Builder $query) => $query->whereIn('branches.id', $visibleBranches->pluck('id')));
            })
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function (Builder $query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category_id'), fn (Builder $query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('is_active', $request->string('status')->toString() === 'active'))
            ->when($request->filled('branch_id'), function (Builder $query) use ($request, $visibleBranches, $hasTenantWideAccess) {
                $branchId = $request->integer('branch_id');

                if ($hasTenantWideAccess || $visibleBranches->contains('id', $branchId)) {
                    $query->whereHas('branches', fn (Builder $query) => $query->where('branches.id', $branchId));
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.service-management.services.index', [
            'services' => $services,
            'categories' => ($isSuperAdmin ? ServiceCategory::withoutTenantScope() : ServiceCategory::query()->where('tenant_id', $tenant->id))->orderBy('name')->get(),
            'branches' => $visibleBranches,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function create(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('create', Service::class);

        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : ($isSuperAdmin ? null : $this->tenant($request));

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'services');
        }

        return view('pages/apps.service-management.services.create', [
            'service' => new Service(['is_active' => true, 'sort_order' => 0, 'default_duration_minutes' => 60, 'default_price' => 0]),
            'categories' => $tenant ? ServiceCategory::withoutTenantScope()->where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('name')->get() : collect(),
            'branches' => $tenant ? Branch::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('name')->get() : collect(),
            'branchConfigs' => collect(),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'selectedTenant' => $tenant,
        ]);
    }

    public function store(StoreServiceRequest $request, ServiceCatalogService $catalog, PlanEntitlementService $entitlements): RedirectResponse
    {
        $tenant = $this->tenantForWrite($request);
        $entitlements->ensureFeature($tenant, 'services');

        $service = $catalog->create($tenant, $request->validated());

        return redirect()
            ->route('services.show', $service)
            ->with('status', 'Service created successfully.');
    }

    public function show(Request $request, Service $service, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('view', $service);
        $entitlements->ensureFeature($service->tenant, 'services');

        $service->load(['category', 'branches']);
        $visibleBranches = $request->user()->hasRole('Super Admin')
            ? Branch::withoutTenantScope()->where('tenant_id', $service->tenant_id)->orderBy('name')->get()
            : $branchContext->availableBranches($request->user());

        if (! $request->user()->hasRole('Super Admin') && ! $branchContext->hasTenantWideBranchAccess($request->user())) {
            abort_unless($service->branches->whereIn('id', $visibleBranches->pluck('id'))->isNotEmpty(), 403);
        }

        return view('pages/apps.service-management.services.show', [
            'service' => $service,
            'branches' => $visibleBranches,
        ]);
    }

    public function edit(Request $request, Service $service, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('update', $service);
        $entitlements->ensureFeature($service->tenant, 'services');

        $service->load('branches');

        return view('pages/apps.service-management.services.edit', [
            'service' => $service,
            'categories' => ServiceCategory::query()->where('tenant_id', $service->tenant_id)->where('is_active', true)->orderBy('name')->get(),
            'branches' => $request->user()->hasRole('Super Admin')
                ? Branch::withoutTenantScope()->where('tenant_id', $service->tenant_id)->orderBy('name')->get()
                : $branchContext->availableBranches($request->user()),
            'branchConfigs' => $service->branches->keyBy('id'),
            'tenants' => collect(),
            'selectedTenant' => $service->tenant,
        ]);
    }

    public function update(UpdateServiceRequest $request, Service $service, ServiceCatalogService $catalog, PlanEntitlementService $entitlements): RedirectResponse
    {
        $entitlements->ensureFeature($service->tenant, 'services');

        $catalog->update($service, $request->validated());

        return redirect()
            ->route('services.show', $service)
            ->with('status', 'Service updated successfully.');
    }

    private function tenant(Request $request): Tenant
    {
        $tenant = $request->user()->tenant;

        abort_unless($tenant, 403);

        return $tenant;
    }

    private function tenantForWrite(Request $request): Tenant
    {
        if ($request->user()->hasRole('Super Admin')) {
            return Tenant::query()->findOrFail($request->integer('tenant_id'));
        }

        return $this->tenant($request);
    }
}
