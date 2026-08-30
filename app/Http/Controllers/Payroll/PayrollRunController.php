<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Tenant;
use App\Services\BranchContext;
use App\Services\Payroll\PayrollApprovalService;
use App\Services\Payroll\PayrollRunService;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollRunController extends Controller
{
    public function index(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', PayrollRun::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'payroll');
        }

        $branches = $isSuperAdmin ? collect() : $branchContext->availableBranches($request->user());
        $runs = ($isSuperAdmin ? PayrollRun::withoutTenantScope() : PayrollRun::query()->where('tenant_id', $tenant->id))
            ->with(['tenant', 'branch', 'period'])
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when(! $isSuperAdmin && ! ($branchContext->hasTenantWideBranchAccess($request->user()) || $request->user()->can('payroll.view_all_branches')), fn (Builder $query) => $query->whereIn('branch_id', $branches->pluck('id')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.payroll.runs.index', [
            'runs' => $runs,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function generate(PayrollPeriod $period, PayrollRunService $payroll, PlanEntitlementService $entitlements): RedirectResponse
    {
        $this->authorize('calculate', [PayrollRun::class, $period]);
        $entitlements->ensureFeature($period->tenant, 'payroll');
        $run = $payroll->generate($period, request()->user());

        return redirect()->route('payroll.runs.show', $run)->with('status', 'Payroll generated successfully.');
    }

    public function show(PayrollRun $payrollRun, PlanEntitlementService $entitlements): View
    {
        $this->authorize('view', $payrollRun);
        $entitlements->ensureFeature($payrollRun->tenant, 'payroll');
        $payrollRun->load(['period', 'branch', 'items.staff', 'items.lines', 'payments']);

        return view('pages/apps.payroll.runs.show', ['run' => $payrollRun]);
    }

    public function submit(PayrollRun $payrollRun, PayrollApprovalService $approval, PlanEntitlementService $entitlements): RedirectResponse
    {
        $this->authorize('approve', $payrollRun);
        $entitlements->ensureFeature($payrollRun->tenant, 'payroll');
        $approval->submitForReview($payrollRun, request()->user());

        return back()->with('status', 'Payroll submitted for review.');
    }

    public function approve(PayrollRun $payrollRun, PayrollApprovalService $approval, PlanEntitlementService $entitlements): RedirectResponse
    {
        $this->authorize('approve', $payrollRun);
        $entitlements->ensureFeature($payrollRun->tenant, 'payroll');
        $approval->approve($payrollRun, request()->user());

        return back()->with('status', 'Payroll approved successfully.');
    }

    public function reject(Request $request, PayrollRun $payrollRun, PayrollApprovalService $approval, PlanEntitlementService $entitlements): RedirectResponse
    {
        $this->authorize('reject', $payrollRun);
        $entitlements->ensureFeature($payrollRun->tenant, 'payroll');
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:3000']]);
        $approval->reject($payrollRun, $request->user(), $data['notes'] ?? null);

        return back()->with('status', 'Payroll returned for correction.');
    }

    private function tenantContext(Request $request): array
    {
        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id') ? Tenant::query()->findOrFail($request->integer('tenant_id')) : ($isSuperAdmin ? null : $request->user()->tenant);

        return [$isSuperAdmin, $tenant];
    }
}
