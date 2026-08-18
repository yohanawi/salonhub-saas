<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\StorePayrollPeriodRequest;
use App\Models\Branch;
use App\Models\PayrollPeriod;
use App\Models\Tenant;
use App\Services\BranchContext;
use App\Services\Payroll\PayrollRunService;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollPeriodController extends Controller
{
    public function index(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', PayrollPeriod::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'payroll');
        }

        $branches = $isSuperAdmin ? Branch::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))->orderBy('name')->get() : $branchContext->availableBranches($request->user());
        $periods = ($isSuperAdmin ? PayrollPeriod::withoutTenantScope() : PayrollPeriod::query()->where('tenant_id', $tenant->id))
            ->with(['tenant', 'branch'])
            ->withCount('runs')
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when($request->filled('branch_id'), fn (Builder $query) => $query->where('branch_id', $request->integer('branch_id')))
            ->latest('start_date')
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.payroll.periods.index', compact('periods', 'branches', 'isSuperAdmin') + [
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
        ]);
    }

    public function create(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('create', PayrollPeriod::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request, true);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'payroll');
        }

        return view('pages/apps.payroll.periods.create', [
            'period' => new PayrollPeriod([
                'name' => now()->format('F Y') . ' Payroll',
                'start_date' => now()->startOfMonth(),
                'end_date' => now()->endOfMonth(),
                'pay_date' => now()->endOfMonth()->addDays(5),
                'status' => PayrollPeriod::STATUS_OPEN,
            ]),
            'branches' => $isSuperAdmin ? Branch::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))->orderBy('name')->get() : $branchContext->availableBranches($request->user()),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'selectedTenant' => $tenant,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function store(StorePayrollPeriodRequest $request, PayrollRunService $payroll, PlanEntitlementService $entitlements): RedirectResponse
    {
        $tenant = $this->tenantForWrite($request);
        $entitlements->ensureFeature($tenant, 'payroll');
        $period = $payroll->createPeriod($tenant, $request->validated(), $request->user());

        return redirect()->route('payroll.periods.show', $period)->with('status', 'Payroll period created successfully.');
    }

    public function show(PayrollPeriod $period, PlanEntitlementService $entitlements): View
    {
        $this->authorize('view', $period);
        $entitlements->ensureFeature($period->tenant, 'payroll');
        $period->load(['branch', 'runs.items.staff']);

        return view('pages/apps.payroll.periods.show', ['period' => $period]);
    }

    private function tenantContext(Request $request, bool $forCreate = false): array
    {
        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id') ? Tenant::query()->findOrFail($request->integer('tenant_id')) : ($isSuperAdmin ? null : $request->user()->tenant);

        return [$isSuperAdmin, $tenant];
    }

    private function tenantForWrite(Request $request): Tenant
    {
        return $request->user()->hasRole('Super Admin') ? Tenant::query()->findOrFail($request->integer('tenant_id')) : $request->user()->tenant;
    }
}
