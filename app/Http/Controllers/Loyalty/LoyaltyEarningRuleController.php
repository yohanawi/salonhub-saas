<?php

namespace App\Http\Controllers\Loyalty;

use App\Http\Controllers\Controller;
use App\Http\Requests\Loyalty\StoreLoyaltyEarningRuleRequest;
use App\Http\Requests\Loyalty\UpdateLoyaltyEarningRuleRequest;
use App\Models\Branch;
use App\Models\LoyaltyEarningRule;
use App\Models\LoyaltyProgram;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Tenant;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoyaltyEarningRuleController extends Controller
{
    public function index(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', LoyaltyEarningRule::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'loyalty_membership');
        }

        $rules = ($isSuperAdmin ? LoyaltyEarningRule::withoutTenantScope() : LoyaltyEarningRule::query()->where('tenant_id', $tenant->id))
            ->with(['tenant', 'program'])
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->orderBy('priority')
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.loyalty-management.rules.index', [
            'rules' => $rules,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function create(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('create', LoyaltyEarningRule::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'loyalty_membership');
        }

        return view('pages/apps.loyalty-management.rules.create', $this->formData($tenant, $isSuperAdmin) + [
            'rule' => new LoyaltyEarningRule([
                'rule_type' => LoyaltyEarningRule::TYPE_SPEND,
                'spend_amount' => 100,
                'points_awarded' => 1,
                'priority' => 100,
                'status' => LoyaltyEarningRule::STATUS_ACTIVE,
            ]),
        ]);
    }

    public function store(StoreLoyaltyEarningRuleRequest $request, PlanEntitlementService $entitlements): RedirectResponse
    {
        $tenant = $this->tenantForWrite($request);
        $entitlements->ensureFeature($tenant, 'loyalty_membership');
        LoyaltyEarningRule::create($this->payload($request->validated(), $tenant->id));

        return redirect()->route('loyalty-management.rules.index')->with('status', 'Earning rule saved successfully.');
    }

    public function edit(LoyaltyEarningRule $earningRule, PlanEntitlementService $entitlements): View
    {
        $this->authorize('update', $earningRule);
        $entitlements->ensureFeature($earningRule->tenant, 'loyalty_membership');

        return view('pages/apps.loyalty-management.rules.edit', $this->formData($earningRule->tenant, false) + [
            'rule' => $earningRule,
        ]);
    }

    public function update(UpdateLoyaltyEarningRuleRequest $request, LoyaltyEarningRule $earningRule, PlanEntitlementService $entitlements): RedirectResponse
    {
        $entitlements->ensureFeature($earningRule->tenant, 'loyalty_membership');
        $earningRule->update($this->payload($request->validated(), $earningRule->tenant_id));

        return redirect()->route('loyalty-management.rules.index')->with('status', 'Earning rule updated successfully.');
    }

    private function payload(array $data, int $tenantId): array
    {
        return [
            'tenant_id' => $tenantId,
            'loyalty_program_id' => $data['loyalty_program_id'],
            'name' => $data['name'],
            'rule_type' => $data['rule_type'],
            'spend_amount' => $data['spend_amount'] ?? 100,
            'points_awarded' => $data['points_awarded'],
            'minimum_purchase_amount' => $data['minimum_purchase_amount'] ?? 0,
            'maximum_points_per_transaction' => $data['maximum_points_per_transaction'] ?? null,
            'service_id' => $data['service_id'] ?? null,
            'service_category_id' => $data['service_category_id'] ?? null,
            'product_id' => $data['product_id'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'priority' => $data['priority'] ?? 100,
            'status' => $data['status'],
        ];
    }

    private function formData(?Tenant $tenant, bool $isSuperAdmin): array
    {
        return [
            'programs' => $tenant ? LoyaltyProgram::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('name')->get() : collect(),
            'branches' => $tenant ? Branch::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('name')->get() : collect(),
            'services' => $tenant ? Service::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('name')->get() : collect(),
            'serviceCategories' => $tenant ? ServiceCategory::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('name')->get() : collect(),
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
