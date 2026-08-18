<?php

namespace App\Http\Controllers\Commission;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commission\PayCommissionPayoutRequest;
use App\Http\Requests\Commission\StoreCommissionPayoutRequest;
use App\Models\Branch;
use App\Models\CommissionPayout;
use App\Models\Staff;
use App\Models\Tenant;
use App\Services\BranchContext;
use App\Services\Commission\CommissionPayoutService;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommissionPayoutController extends Controller
{
    public function index(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', CommissionPayout::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'staff_commissions');
        }

        $branches = $isSuperAdmin
            ? Branch::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))->orderBy('name')->get()
            : $branchContext->availableBranches($request->user());

        $payouts = ($isSuperAdmin ? CommissionPayout::withoutTenantScope() : CommissionPayout::query()->where('tenant_id', $tenant->id))
            ->with(['tenant', 'branch', 'staff'])
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when(! $isSuperAdmin && ! ($branchContext->hasTenantWideBranchAccess($request->user()) || $request->user()->can('commissions.view_all_branches')), fn (Builder $query) => $query->whereIn('branch_id', $branches->pluck('id')))
            ->when($request->filled('staff_id'), fn (Builder $query) => $query->where('staff_id', $request->integer('staff_id')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.commission-management.payouts.index', [
            'payouts' => $payouts,
            'branches' => $branches,
            'staffMembers' => $tenant ? Staff::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('first_name')->get() : collect(),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function create(Request $request, BranchContext $branchContext): View
    {
        $this->authorize('create', CommissionPayout::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request, true);

        return view('pages/apps.commission-management.payouts.create', [
            'payout' => new CommissionPayout([
                'period_start' => now()->startOfMonth(),
                'period_end' => now()->endOfMonth(),
                'adjustment_amount' => 0,
            ]),
            'branches' => $isSuperAdmin ? Branch::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))->orderBy('name')->get() : $branchContext->availableBranches($request->user()),
            'staffMembers' => $tenant ? Staff::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('first_name')->get() : collect(),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'selectedTenant' => $tenant,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function store(StoreCommissionPayoutRequest $request, CommissionPayoutService $payouts, PlanEntitlementService $entitlements): RedirectResponse
    {
        $tenant = $this->tenantForWrite($request);
        $entitlements->ensureFeature($tenant, 'staff_commissions');
        $staff = Staff::withoutTenantScope()->where('tenant_id', $tenant->id)->findOrFail($request->integer('staff_id'));

        $payout = $payouts->create($tenant, $staff, $request->validated(), $request->user());

        return redirect()->route('commission-management.payouts.show', $payout)->with('status', 'Commission payout created successfully.');
    }

    public function show(CommissionPayout $payout): View
    {
        $this->authorize('view', $payout);
        $payout->load(['tenant', 'branch', 'staff', 'items.commission.invoice', 'createdBy', 'approvedBy', 'paidBy']);

        return view('pages/apps.commission-management.payouts.show', [
            'payout' => $payout,
        ]);
    }

    public function approve(CommissionPayout $payout, CommissionPayoutService $payouts): RedirectResponse
    {
        $this->authorize('approve', $payout);
        $payouts->approve($payout, request()->user());

        return back()->with('status', 'Payout approved successfully.');
    }

    public function pay(PayCommissionPayoutRequest $request, CommissionPayout $payout, CommissionPayoutService $payouts): RedirectResponse
    {
        $payouts->pay($payout, $request->validated(), $request->user());

        return back()->with('status', 'Payout marked as paid successfully.');
    }

    private function tenantContext(Request $request, bool $forCreate = false): array
    {
        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : ($isSuperAdmin ? null : $request->user()->tenant);

        return [$isSuperAdmin, $tenant];
    }

    private function tenantForWrite(Request $request): Tenant
    {
        return $request->user()->hasRole('Super Admin')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : $request->user()->tenant;
    }
}
