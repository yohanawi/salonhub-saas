<?php

namespace App\Http\Controllers\Commission;

use App\Http\Controllers\Controller;
use App\Models\CommissionPayout;
use App\Models\StaffCommission;
use App\Models\Tenant;
use App\Services\BranchContext;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommissionDashboardController extends Controller
{
    public function __invoke(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', StaffCommission::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'staff_commissions');
        }

        $branches = $isSuperAdmin ? collect() : $branchContext->availableBranches($request->user());
        $base = StaffCommission::withoutTenantScope()
            ->with(['staff', 'branch', 'invoice'])
            ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))
            ->when(! $isSuperAdmin && $request->user()->can('commissions.view_own') && ! $request->user()->can('commissions.view'), fn (Builder $query) => $query->where('staff_id', $request->user()->staffProfile?->id ?? 0))
            ->when(! $isSuperAdmin && $request->user()->can('commissions.view') && ! ($branchContext->hasTenantWideBranchAccess($request->user()) || $request->user()->can('commissions.view_all_branches')), fn (Builder $query) => $query->whereIn('branch_id', $branches->pluck('id')));

        return view('pages/apps.commission-management.dashboard', [
            'earnedTotal' => (clone $base)->whereIn('status', [StaffCommission::STATUS_EARNED, StaffCommission::STATUS_APPROVED, StaffCommission::STATUS_PAID])->sum('commission_amount'),
            'pendingApproval' => (clone $base)->where('status', StaffCommission::STATUS_EARNED)->sum('commission_amount'),
            'approvedUnpaid' => (clone $base)->where('status', StaffCommission::STATUS_APPROVED)->sum('commission_amount'),
            'paidThisMonth' => (clone $base)->where('status', StaffCommission::STATUS_PAID)->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('commission_amount'),
            'recentCommissions' => (clone $base)->latest('earned_at')->take(10)->get(),
            'recentPayouts' => CommissionPayout::withoutTenantScope()
                ->with(['staff', 'branch'])
                ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))
                ->latest()
                ->take(6)
                ->get(),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    private function tenantContext(Request $request): array
    {
        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : ($isSuperAdmin ? null : $request->user()->tenant);

        return [$isSuperAdmin, $tenant];
    }
}
