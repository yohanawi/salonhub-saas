<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PayrollRun;
use App\Models\Tenant;
use App\Services\BranchContext;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollDashboardController extends Controller
{
    public function __invoke(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', PayrollRun::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'payroll');
        }

        $branches = $isSuperAdmin ? collect() : $branchContext->availableBranches($request->user());
        $base = PayrollRun::withoutTenantScope()
            ->with(['period', 'branch'])
            ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))
            ->when(! $isSuperAdmin && ! ($branchContext->hasTenantWideBranchAccess($request->user()) || $request->user()->can('payroll.view_all_branches')), fn (Builder $query) => $query->whereIn('branch_id', $branches->pluck('id')));

        return view('pages/apps.payroll.dashboard', [
            'grossPayroll' => (clone $base)->sum('gross_pay'),
            'netPayroll' => (clone $base)->sum('net_pay'),
            'paidPayroll' => (clone $base)->sum('paid_amount'),
            'outstandingPayroll' => (clone $base)->sum('balance_amount'),
            'pendingApprovals' => (clone $base)->whereIn('status', [PayrollRun::STATUS_CALCULATED, PayrollRun::STATUS_UNDER_REVIEW])->count(),
            'recentRuns' => (clone $base)->latest()->take(8)->get(),
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
