<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\Tenant;
use App\Services\BranchContext;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollReportController extends Controller
{
    public function index(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        abort_unless($request->user()->hasRole('Super Admin') || $request->user()->can('payroll.report.view'), 403);
        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id') ? Tenant::query()->findOrFail($request->integer('tenant_id')) : ($isSuperAdmin ? null : $request->user()->tenant);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'payroll');
        }

        $branches = $isSuperAdmin ? Branch::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))->orderBy('name')->get() : $branchContext->availableBranches($request->user());
        $base = PayrollItem::withoutTenantScope()
            ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))
            ->when(! $isSuperAdmin && ! ($branchContext->hasTenantWideBranchAccess($request->user()) || $request->user()->can('payroll.view_all_branches')), fn (Builder $query) => $query->whereIn('branch_id', $branches->pluck('id')))
            ->when($request->filled('branch_id'), fn (Builder $query) => $query->where('branch_id', $request->integer('branch_id')));

        return view('pages/apps.payroll.reports.index', [
            'grossPayroll' => (clone $base)->sum('gross_pay'),
            'commissionTotal' => (clone $base)->sum('commission_amount'),
            'deductionsTotal' => (clone $base)->sum('total_deductions'),
            'netPayroll' => (clone $base)->sum('net_pay'),
            'paidPayroll' => (clone $base)->sum('paid_amount'),
            'byBranch' => (clone $base)->selectRaw('branch_id, sum(net_pay) as total')->with('branch')->groupBy('branch_id')->orderByDesc('total')->get(),
            'runs' => PayrollRun::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))->latest()->take(8)->get(),
            'branches' => $branches,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }
}
