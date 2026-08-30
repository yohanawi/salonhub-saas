<?php

namespace App\Http\Controllers\Loyalty;

use App\Http\Controllers\Controller;
use App\Http\Requests\Loyalty\StoreMembershipPlanRequest;
use App\Http\Requests\Loyalty\UpdateMembershipPlanRequest;
use App\Models\MembershipBenefit;
use App\Models\MembershipPlan;
use App\Models\Product;
use App\Models\Service;
use App\Models\Tenant;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MembershipPlanController extends Controller
{
    public function index(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', MembershipPlan::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'loyalty_membership');
        }

        $plans = ($isSuperAdmin ? MembershipPlan::withoutTenantScope() : MembershipPlan::query()->where('tenant_id', $tenant->id))
            ->with(['tenant', 'benefits'])
            ->withCount('memberships')
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.loyalty-management.membership-plans.index', [
            'plans' => $plans,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function create(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('create', MembershipPlan::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'loyalty_membership');
        }

        return view('pages/apps.loyalty-management.membership-plans.create', $this->formData($tenant, $isSuperAdmin) + [
            'plan' => new MembershipPlan([
                'duration_type' => 'months',
                'duration_value' => 12,
                'billing_type' => 'one_time',
                'status' => MembershipPlan::STATUS_ACTIVE,
            ]),
        ]);
    }

    public function store(StoreMembershipPlanRequest $request, PlanEntitlementService $entitlements): RedirectResponse
    {
        $tenant = $this->tenantForWrite($request);
        $entitlements->ensureFeature($tenant, 'loyalty_membership');
        $plan = DB::transaction(function () use ($request, $tenant) {
            $plan = MembershipPlan::create($this->payload($request->validated(), $tenant->id) + ['created_by' => $request->user()->id]);
            $this->syncBenefits($plan, $request->validated()['benefits'] ?? []);

            return $plan;
        });

        return redirect()->route('loyalty-management.membership-plans.edit', $plan)->with('status', 'Membership plan saved successfully.');
    }

    public function edit(MembershipPlan $membershipPlan, PlanEntitlementService $entitlements): View
    {
        $this->authorize('update', $membershipPlan);
        $entitlements->ensureFeature($membershipPlan->tenant, 'loyalty_membership');
        $membershipPlan->load('benefits');

        return view('pages/apps.loyalty-management.membership-plans.edit', $this->formData($membershipPlan->tenant, false) + [
            'plan' => $membershipPlan,
        ]);
    }

    public function update(UpdateMembershipPlanRequest $request, MembershipPlan $membershipPlan, PlanEntitlementService $entitlements): RedirectResponse
    {
        $entitlements->ensureFeature($membershipPlan->tenant, 'loyalty_membership');
        DB::transaction(function () use ($request, $membershipPlan) {
            $membershipPlan->update($this->payload($request->validated(), $membershipPlan->tenant_id));
            $this->syncBenefits($membershipPlan, $request->validated()['benefits'] ?? []);
        });

        return redirect()->route('loyalty-management.membership-plans.index')->with('status', 'Membership plan updated successfully.');
    }

    private function payload(array $data, int $tenantId): array
    {
        return [
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'code' => str($data['code'])->slug('_')->toString(),
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'duration_type' => $data['duration_type'],
            'duration_value' => $data['duration_value'],
            'billing_type' => $data['billing_type'],
            'joining_fee' => $data['joining_fee'] ?? 0,
            'status' => $data['status'],
            'is_featured' => (bool) ($data['is_featured'] ?? false),
        ];
    }

    private function syncBenefits(MembershipPlan $plan, array $benefits): void
    {
        $plan->benefits()->delete();

        foreach ($benefits as $benefit) {
            if (blank($benefit['benefit_type'] ?? null)) {
                continue;
            }

            $plan->benefits()->create([
                'tenant_id' => $plan->tenant_id,
                'benefit_type' => $benefit['benefit_type'],
                'discount_type' => $benefit['discount_type'] ?? MembershipBenefit::DISCOUNT_PERCENTAGE,
                'discount_value' => $benefit['discount_value'] ?? 0,
                'service_id' => $benefit['service_id'] ?? null,
                'product_id' => $benefit['product_id'] ?? null,
                'loyalty_multiplier' => $benefit['loyalty_multiplier'] ?? null,
                'priority' => $benefit['priority'] ?? 100,
                'status' => $benefit['status'] ?? MembershipBenefit::STATUS_ACTIVE,
            ]);
        }
    }

    private function formData(?Tenant $tenant, bool $isSuperAdmin): array
    {
        return [
            'services' => $tenant ? Service::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('name')->get() : collect(),
            'products' => $tenant ? Product::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('name')->get() : collect(),
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
