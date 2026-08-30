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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffCommissionController extends Controller
{
    public function index(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', StaffCommission::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'staff_commissions');
        }

        $branches = $isSuperAdmin
            ? Branch::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))->orderBy('name')->get()
            : $branchContext->availableBranches($request->user());

        $commissions = ($isSuperAdmin ? StaffCommission::withoutTenantScope() : StaffCommission::query()->where('tenant_id', $tenant->id))
            ->with(['tenant', 'branch', 'staff', 'invoice', 'invoiceItem.service', 'service', 'product'])
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when(! $isSuperAdmin && $request->user()->can('commissions.view_own') && ! $request->user()->can('commissions.view'), fn (Builder $query) => $query->where('staff_id', $request->user()->staffProfile?->id ?? 0))
            ->when(! $isSuperAdmin && $request->user()->can('commissions.view') && ! ($branchContext->hasTenantWideBranchAccess($request->user()) || $request->user()->can('commissions.view_all_branches')), fn (Builder $query) => $query->whereIn('branch_id', $branches->pluck('id')))
            ->when($request->filled('branch_id'), fn (Builder $query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('staff_id'), fn (Builder $query) => $query->where('staff_id', $request->integer('staff_id')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('earned_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('earned_at', '<=', $request->date('date_to')))
            ->latest('earned_at')
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.commission-management.ledger.index', [
            'commissions' => $commissions,
            'branches' => $branches,
            'staffMembers' => $tenant ? Staff::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('first_name')->get() : collect(),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function show(StaffCommission $commission, PlanEntitlementService $entitlements): View
    {
        $this->authorize('view', $commission);
        $entitlements->ensureFeature($commission->tenant, 'staff_commissions');

        $commission->load(['tenant', 'branch', 'staff', 'customer', 'appointment', 'invoice', 'invoiceItem', 'service', 'product', 'rule', 'approvedBy', 'paidBy']);

        return view('pages/apps.commission-management.ledger.show', [
            'commission' => $commission,
        ]);
    }

    public function approve(Request $request, StaffCommission $commission): RedirectResponse
    {
        $this->authorize('approve', $commission);
        abort_unless($commission->status === StaffCommission::STATUS_EARNED, 422, 'Only earned commissions can be approved.');

        $commission->update([
            'status' => StaffCommission::STATUS_APPROVED,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('status', 'Commission approved successfully.');
    }

    public function reject(Request $request, StaffCommission $commission): RedirectResponse
    {
        $this->authorize('reject', $commission);
        abort_unless($commission->status === StaffCommission::STATUS_EARNED, 422, 'Only earned commissions can be rejected.');

        $data = $request->validate(['notes' => ['nullable', 'string', 'max:3000']]);

        $commission->update([
            'status' => StaffCommission::STATUS_CANCELLED,
            'approved_by' => null,
            'approved_at' => null,
            'notes' => $data['notes'] ?? 'Rejected.',
        ]);

        return back()->with('status', 'Commission rejected successfully.');
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
