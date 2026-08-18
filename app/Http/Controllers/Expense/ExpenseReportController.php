<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Tenant;
use App\Models\Vendor;
use App\Services\BranchContext;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseReportController extends Controller
{
    public function index(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        abort_unless($request->user()->hasRole('Super Admin') || $request->user()->can('expense_reports.view'), 403);

        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : ($isSuperAdmin ? null : $request->user()->tenant);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'expenses');
        }

        $branches = $isSuperAdmin
            ? Branch::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))->orderBy('name')->get()
            : $branchContext->availableBranches($request->user());

        $base = Expense::withoutTenantScope()
            ->where('expense_status', '!=', Expense::STATUS_CANCELLED)
            ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))
            ->when(! $isSuperAdmin && ! ($branchContext->hasTenantWideBranchAccess($request->user()) || $request->user()->can('expenses.view_all_branches')), fn (Builder $query) => $query->whereIn('branch_id', $branches->pluck('id')))
            ->when($request->filled('branch_id'), fn (Builder $query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('category_id'), fn (Builder $query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->filled('vendor_id'), fn (Builder $query) => $query->where('vendor_id', $request->integer('vendor_id')))
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('expense_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('expense_date', '<=', $request->date('date_to')));

        return view('pages/apps.expense-management.reports.index', [
            'totalExpenses' => (clone $base)->sum('total_amount'),
            'totalPaid' => (clone $base)->sum('paid_amount'),
            'totalOutstanding' => (clone $base)->sum('balance_amount'),
            'byCategory' => (clone $base)->selectRaw('category_id, sum(total_amount) as total')->with('category')->groupBy('category_id')->orderByDesc('total')->get(),
            'byBranch' => (clone $base)->selectRaw('branch_id, sum(total_amount) as total')->with('branch')->groupBy('branch_id')->orderByDesc('total')->get(),
            'byVendor' => (clone $base)->selectRaw('vendor_id, sum(total_amount) as total')->with('vendor')->groupBy('vendor_id')->orderByDesc('total')->get(),
            'branches' => $branches,
            'categories' => $tenant ? ExpenseCategory::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('name')->get() : ExpenseCategory::withoutTenantScope()->orderBy('name')->get(),
            'vendors' => $tenant ? Vendor::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('name')->get() : Vendor::withoutTenantScope()->orderBy('name')->get(),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }
}
