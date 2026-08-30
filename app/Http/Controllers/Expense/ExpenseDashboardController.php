<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Tenant;
use App\Services\BranchContext;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseDashboardController extends Controller
{
    public function __invoke(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', Expense::class);

        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : ($isSuperAdmin ? null : $request->user()->tenant);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'expenses');
        }

        $branches = $isSuperAdmin ? collect() : $branchContext->availableBranches($request->user());
        $base = Expense::withoutTenantScope()
            ->with(['branch', 'category', 'vendor'])
            ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))
            ->when(! $isSuperAdmin && ! ($branchContext->hasTenantWideBranchAccess($request->user()) || $request->user()->can('expenses.view_all_branches')), fn (Builder $query) => $query->whereIn('branch_id', $branches->pluck('id')))
            ->where('expense_status', '!=', Expense::STATUS_CANCELLED);

        return view('pages/apps.expense-management.dashboard', [
            'todayTotal' => (clone $base)->whereDate('expense_date', today())->sum('total_amount'),
            'monthTotal' => (clone $base)->whereBetween('expense_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('total_amount'),
            'pendingPayments' => (clone $base)->whereIn('payment_status', [Expense::PAYMENT_UNPAID, Expense::PAYMENT_PARTIAL])->sum('balance_amount'),
            'pendingApprovals' => (clone $base)->where('approval_status', Expense::APPROVAL_PENDING)->count(),
            'recentExpenses' => (clone $base)->latest()->take(8)->get(),
            'categoryTotals' => (clone $base)
                ->selectRaw('category_id, sum(total_amount) as total')
                ->with('category')
                ->groupBy('category_id')
                ->orderByDesc('total')
                ->take(8)
                ->get(),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }
}
