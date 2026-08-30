<?php

namespace App\Http\Controllers\Loyalty;

use App\Http\Controllers\Controller;
use App\Models\CustomerLoyaltyAccount;
use App\Models\CustomerMembership;
use App\Models\LoyaltyPointTransaction;
use App\Models\LoyaltyProgram;
use App\Models\MembershipPlan;
use App\Models\Tenant;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoyaltyDashboardController extends Controller
{
    public function __invoke(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', LoyaltyProgram::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'loyalty_membership');
        }

        $accountBase = CustomerLoyaltyAccount::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id));
        $transactionBase = LoyaltyPointTransaction::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id));
        $membershipBase = CustomerMembership::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id));

        return view('pages/apps.loyalty-management.dashboard', [
            'activeLoyaltyMembers' => (clone $accountBase)->where('status', CustomerLoyaltyAccount::STATUS_ACTIVE)->count(),
            'pointsIssued' => (clone $transactionBase)->where('points', '>', 0)->sum('points'),
            'pointsRedeemed' => abs((clone $transactionBase)->where('type', LoyaltyPointTransaction::TYPE_REDEEM)->sum('points')),
            'activeMemberships' => (clone $membershipBase)->where('status', CustomerMembership::STATUS_ACTIVE)->count(),
            'recentTransactions' => (clone $transactionBase)->with(['customer', 'source'])->latest()->take(8)->get(),
            'expiringMemberships' => (clone $membershipBase)->with(['customer', 'plan'])->where('status', CustomerMembership::STATUS_ACTIVE)->whereDate('end_date', '<=', today()->addDays(30))->orderBy('end_date')->take(8)->get(),
            'plans' => MembershipPlan::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))->withCount('memberships')->orderBy('name')->get(),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    private function tenantContext(Request $request): array
    {
        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id') ? Tenant::query()->findOrFail($request->integer('tenant_id')) : ($isSuperAdmin ? null : $request->user()->tenant);

        return [$isSuperAdmin, $tenant];
    }
}
