<?php

namespace App\Http\Controllers\Commission;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\StaffCommission;
use App\Models\Tenant;
use App\Services\BranchContext;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommissionReportController extends Controller
{
    public function index(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        abort_unless($request->user()->hasRole('Super Admin') || $request->user()->can('commission_reports.view'), 403);

        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : ($isSuperAdmin ? null : $request->user()->tenant);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'staff_commissions');
        }

        $branches = $isSuperAdmin
            ? Branch::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))->orderBy('name')->get()
            : $branchContext->availableBranches($request->user());

        $base = StaffCommission::withoutTenantScope()
            ->whereNotIn('status', [StaffCommission::STATUS_CANCELLED])
            ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))
            ->when(! $isSuperAdmin && ! ($branchContext->hasTenantWideBranchAccess($request->user()) || $request->user()->can('commissions.view_all_branches')), fn (Builder $query) => $query->whereIn('branch_id', $branches->pluck('id')))
            ->when($request->filled('branch_id'), fn (Builder $query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('staff_id'), fn (Builder $query) => $query->where('staff_id', $request->integer('staff_id')))
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('earned_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('earned_at', '<=', $request->date('date_to')));

        return view('pages/apps.commission-management.reports.index', [
            'totalEarned' => (clone $base)->whereIn('status', [StaffCommission::STATUS_EARNED, StaffCommission::STATUS_APPROVED, StaffCommission::STATUS_PAID])->sum('commission_amount'),
            'totalApproved' => (clone $base)->where('status', StaffCommission::STATUS_APPROVED)->sum('commission_amount'),
            'totalPaid' => (clone $base)->where('status', StaffCommission::STATUS_PAID)->sum('commission_amount'),
            'totalReversed' => (clone $base)->where('status', StaffCommission::STATUS_REVERSED)->sum('commission_amount'),
            'byStaff' => (clone $base)->selectRaw('staff_id, sum(commission_amount) as total')->with('staff')->groupBy('staff_id')->orderByDesc('total')->get(),
            'byBranch' => (clone $base)->selectRaw('branch_id, sum(commission_amount) as total')->with('branch')->groupBy('branch_id')->orderByDesc('total')->get(),
            'branches' => $branches,
            'staffMembers' => $tenant ? Staff::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('first_name')->get() : collect(),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }
}
