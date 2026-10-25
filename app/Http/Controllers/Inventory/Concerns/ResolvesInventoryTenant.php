<?php

namespace App\Http\Controllers\Inventory\Concerns;

use App\Models\Branch;
use App\Models\Tenant;
use App\Services\BranchContext;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait ResolvesInventoryTenant
{
    /**
     * Salon users work in their own salon; super admins may pick one with ?tenant_id=.
     *
     * @return array{0: bool, 1: ?Tenant}
     */
    protected function inventoryTenant(Request $request): array
    {
        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin
            ? ($request->filled('tenant_id') ? Tenant::query()->findOrFail($request->integer('tenant_id')) : null)
            : $request->user()->tenant;

        abort_unless($isSuperAdmin || $tenant, 403);

        if ($tenant) {
            app(PlanEntitlementService::class)->ensureFeature($tenant, 'inventory');
        }

        return [$isSuperAdmin, $tenant];
    }

    protected function visibleBranches(Request $request, ?Tenant $tenant): Collection
    {
        if ($request->user()->hasRole('Super Admin')) {
            return Branch::withoutTenantScope()
                ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))
                ->orderBy('name')
                ->get();
        }

        return app(BranchContext::class)->availableBranches($request->user());
    }

    protected function hasTenantWideAccess(Request $request): bool
    {
        return $request->user()->hasRole('Super Admin')
            || app(BranchContext::class)->hasTenantWideBranchAccess($request->user());
    }

    /**
     * A branch the user may act on, from the submitted branch_id.
     */
    protected function writableBranch(Request $request, int $branchId, ?Tenant $tenant = null): Branch
    {
        $branch = Branch::withoutTenantScope()
            ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))
            ->findOrFail($branchId);

        abort_unless(
            $request->user()->hasRole('Super Admin') || app(BranchContext::class)->canAccess($request->user(), $branch),
            403
        );

        app(PlanEntitlementService::class)->ensureFeature($branch->tenant, 'inventory');

        return $branch;
    }
}
