<?php

namespace App\Http\Controllers\Loyalty;

use App\Http\Controllers\Controller;
use App\Http\Requests\Loyalty\CancelMembershipRequest;
use App\Http\Requests\Loyalty\StoreCustomerMembershipRequest;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerMembership;
use App\Models\MembershipPlan;
use App\Models\PaymentMethod;
use App\Models\Tenant;
use App\Services\Billing\PaymentMethodService;
use App\Services\Membership\MembershipService;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerMembershipController extends Controller
{
    public function index(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', CustomerMembership::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'loyalty_membership');
        }

        $memberships = (CustomerMembership::withoutTenantScope())
            ->with(['tenant', 'customer', 'plan'])
            ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.loyalty-management.memberships.index', [
            'memberships' => $memberships,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function create(Request $request, PaymentMethodService $paymentMethods, PlanEntitlementService $entitlements): View
    {
        $this->authorize('create', CustomerMembership::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'loyalty_membership');
            $paymentMethods->ensureDefaults($tenant);
        }

        return view('pages/apps.loyalty-management.memberships.create', $this->formData($tenant, $isSuperAdmin) + [
            'membership' => new CustomerMembership(['start_date' => today(), 'status' => CustomerMembership::STATUS_ACTIVE]),
        ]);
    }

    public function store(StoreCustomerMembershipRequest $request, MembershipService $memberships, PlanEntitlementService $entitlements): RedirectResponse
    {
        $tenant = $this->tenantForWrite($request);
        $entitlements->ensureFeature($tenant, 'loyalty_membership');
        $customer = Customer::withoutTenantScope()->where('tenant_id', $tenant->id)->findOrFail($request->integer('customer_id'));
        $plan = MembershipPlan::withoutTenantScope()->where('tenant_id', $tenant->id)->findOrFail($request->integer('membership_plan_id'));
        $membership = $memberships->purchase($tenant, $customer, $plan, $request->validated(), $request->user());

        return redirect()->route('loyalty-management.memberships.show', $membership)->with('status', 'Membership purchased successfully.');
    }

    public function show(CustomerMembership $membership, PlanEntitlementService $entitlements): View
    {
        $this->authorize('view', $membership);
        $entitlements->ensureFeature($membership->tenant, 'loyalty_membership');
        $membership->load(['customer', 'plan.benefits', 'usages.benefit', 'invoice']);

        return view('pages/apps.loyalty-management.memberships.show', ['membership' => $membership]);
    }

    public function cancel(CancelMembershipRequest $request, CustomerMembership $membership, MembershipService $memberships, PlanEntitlementService $entitlements): RedirectResponse
    {
        $entitlements->ensureFeature($membership->tenant, 'loyalty_membership');
        $memberships->cancel($membership, $request->user(), $request->string('cancellation_reason')->toString());

        return back()->with('status', 'Membership cancelled successfully.');
    }

    private function formData(?Tenant $tenant, bool $isSuperAdmin): array
    {
        return [
            'customers' => $tenant ? Customer::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('first_name')->get() : collect(),
            'plans' => $tenant ? MembershipPlan::withoutTenantScope()->where('tenant_id', $tenant->id)->where('status', MembershipPlan::STATUS_ACTIVE)->orderBy('name')->get() : collect(),
            'branches' => $tenant ? Branch::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('name')->get() : collect(),
            'paymentMethods' => $tenant ? PaymentMethod::withoutTenantScope()->where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('name')->get() : collect(),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'selectedTenant' => $tenant,
            'isSuperAdmin' => $isSuperAdmin,
        ];
    }

    private function tenantContext(Request $request): array
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
