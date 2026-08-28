<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\StoreServiceCategoryRequest;
use App\Http\Requests\Service\UpdateServiceCategoryRequest;
use App\Models\ServiceCategory;
use App\Models\Tenant;
use App\Services\BranchContext;
use App\Services\PlanEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ServiceCategoryController extends Controller
{
    public function index(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', ServiceCategory::class);

        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin ? null : $this->tenant($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'services');
        }

        $categories = ($isSuperAdmin ? ServiceCategory::withoutTenantScope() : ServiceCategory::query()->where('tenant_id', $tenant->id))
            ->with('tenant')
            ->withCount('services')
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn ($query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('is_active', $request->string('status')->toString() === 'active');
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $viewData = [
            'categories' => $categories,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ];

        if ($request->ajax()) {
            return view('pages/apps.service-management.categories._results', $viewData);
        }

        return view('pages/apps.service-management.categories.index', $viewData);
    }

    public function create(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('create', ServiceCategory::class);
        if (! $request->user()->hasRole('Super Admin')) {
            $entitlements->ensureFeature($this->tenant($request), 'services');
        }

        return view('pages/apps.service-management.categories.create', [
            'category' => new ServiceCategory(['is_active' => true, 'sort_order' => 0]),
            'tenants' => $request->user()->hasRole('Super Admin') ? Tenant::query()->orderBy('name')->get() : collect(),
        ]);
    }

    public function store(StoreServiceCategoryRequest $request, PlanEntitlementService $entitlements): RedirectResponse
    {
        $tenant = $this->tenantForWrite($request);
        $entitlements->ensureFeature($tenant, 'services');

        $validated = $request->validated();

        $category = $tenant->serviceCategories()->create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()
            ->route('service-categories.edit', $category)
            ->with('status', 'Service category created successfully.');
    }

    public function edit(Request $request, ServiceCategory $serviceCategory, PlanEntitlementService $entitlements): View
    {
        $this->authorize('update', $serviceCategory);
        $entitlements->ensureFeature($serviceCategory->tenant, 'services');

        return view('pages/apps.service-management.categories.edit', [
            'category' => $serviceCategory,
            'tenants' => collect(),
        ]);
    }

    public function update(UpdateServiceCategoryRequest $request, ServiceCategory $serviceCategory, PlanEntitlementService $entitlements): RedirectResponse
    {
        $entitlements->ensureFeature($serviceCategory->tenant, 'services');

        $validated = $request->validated();

        $serviceCategory->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()
            ->route('service-categories.index')
            ->with('status', 'Service category updated successfully.');
    }

    public function destroy(Request $request, ServiceCategory $serviceCategory): RedirectResponse
    {
        $this->authorize('delete', $serviceCategory);

        $serviceCategory->delete();

        return redirect()
            ->route('service-categories.index')
            ->with('status', 'Service category archived successfully. Existing services were not deleted.');
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
