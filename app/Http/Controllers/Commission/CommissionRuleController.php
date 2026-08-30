<?php

namespace App\Http\Controllers\Commission;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commission\StoreCommissionRuleRequest;
use App\Http\Requests\Commission\UpdateCommissionRuleRequest;
use App\Models\Branch;
use App\Models\CommissionRule;
use App\Models\Product;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommissionRuleController extends Controller
{
    public function index(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', CommissionRule::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'staff_commissions');
        }

        $rules = ($isSuperAdmin ? CommissionRule::withoutTenantScope() : CommissionRule::query()->where('tenant_id', $tenant->id))
            ->with(['tenant', 'branch', 'staff', 'service', 'product'])
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->orderBy('priority')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.commission-management.rules.index', [
            'rules' => $rules,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', CommissionRule::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request, true);

        return view('pages/apps.commission-management.rules.create', $this->formData($tenant, $isSuperAdmin) + [
            'rule' => new CommissionRule([
                'commission_scope' => CommissionRule::SCOPE_STAFF,
                'commission_type' => 'percentage',
                'commission_value' => 0,
                'calculate_on' => 'net_after_discount',
                'priority' => 30,
                'is_active' => true,
            ]),
        ]);
    }

    public function store(StoreCommissionRuleRequest $request, PlanEntitlementService $entitlements): RedirectResponse
    {
        $tenant = $this->tenantForWrite($request);
        $entitlements->ensureFeature($tenant, 'staff_commissions');
        $data = $this->normalizeRule($tenant, $request->validated());

        CommissionRule::create($data + [
            'tenant_id' => $tenant->id,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('commission-management.rules.index')->with('status', 'Commission rule created successfully.');
    }

    public function edit(CommissionRule $rule): View
    {
        $this->authorize('update', $rule);

        return view('pages/apps.commission-management.rules.edit', $this->formData($rule->tenant, false) + [
            'rule' => $rule,
            'selectedTenant' => $rule->tenant,
        ]);
    }

    public function update(UpdateCommissionRuleRequest $request, CommissionRule $rule, PlanEntitlementService $entitlements): RedirectResponse
    {
        $entitlements->ensureFeature($rule->tenant, 'staff_commissions');
        $rule->update($this->normalizeRule($rule->tenant, $request->validated()) + [
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('commission-management.rules.index')->with('status', 'Commission rule updated successfully.');
    }

    public function destroy(CommissionRule $rule): RedirectResponse
    {
        $this->authorize('delete', $rule);

        if ($rule->staffCommissions()->exists()) {
            return back()->with('error', 'This rule has commission history and cannot be deleted.');
        }

        $rule->delete();

        return redirect()->route('commission-management.rules.index')->with('status', 'Commission rule deleted successfully.');
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

    private function formData(?Tenant $tenant, bool $isSuperAdmin): array
    {
        return [
            'branches' => $tenant ? Branch::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('name')->get() : collect(),
            'staffMembers' => $tenant ? Staff::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('first_name')->get() : collect(),
            'services' => $tenant ? Service::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('name')->get() : collect(),
            'products' => $tenant ? Product::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('name')->get() : collect(),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'selectedTenant' => $tenant,
        ];
    }

    private function normalizeRule(Tenant $tenant, array $data): array
    {
        $scope = $data['commission_scope'];
        $requirements = [
            CommissionRule::SCOPE_BRANCH => ['branch_id'],
            CommissionRule::SCOPE_STAFF => ['staff_id'],
            CommissionRule::SCOPE_SERVICE => ['service_id'],
            CommissionRule::SCOPE_STAFF_SERVICE => ['staff_id', 'service_id'],
            CommissionRule::SCOPE_PRODUCT => ['product_id'],
            CommissionRule::SCOPE_STAFF_PRODUCT => ['staff_id', 'product_id'],
        ];

        foreach ($requirements[$scope] ?? [] as $field) {
            abort_if(empty($data[$field]), 422, str($field)->replace('_', ' ')->headline() . ' is required for this scope.');
        }

        $this->assertTenantId($tenant, Branch::class, $data['branch_id'] ?? null, 'branch_id');
        $this->assertTenantId($tenant, Staff::class, $data['staff_id'] ?? null, 'staff_id');
        $this->assertTenantId($tenant, Service::class, $data['service_id'] ?? null, 'service_id');
        $this->assertTenantId($tenant, Product::class, $data['product_id'] ?? null, 'product_id');

        return [
            'branch_id' => in_array($scope, [CommissionRule::SCOPE_BRANCH], true) ? ($data['branch_id'] ?? null) : null,
            'staff_id' => in_array($scope, [CommissionRule::SCOPE_STAFF, CommissionRule::SCOPE_STAFF_SERVICE, CommissionRule::SCOPE_STAFF_PRODUCT], true) ? ($data['staff_id'] ?? null) : null,
            'service_id' => in_array($scope, [CommissionRule::SCOPE_SERVICE, CommissionRule::SCOPE_STAFF_SERVICE], true) ? ($data['service_id'] ?? null) : null,
            'product_id' => in_array($scope, [CommissionRule::SCOPE_PRODUCT, CommissionRule::SCOPE_STAFF_PRODUCT], true) ? ($data['product_id'] ?? null) : null,
            'commission_scope' => $scope,
            'commission_type' => $data['commission_type'],
            'commission_value' => $data['commission_value'],
            'calculate_on' => $data['calculate_on'],
            'priority' => $data['priority'] ?? $this->priorityFor($scope),
            'effective_from' => $data['effective_from'] ?? null,
            'effective_to' => $data['effective_to'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }

    private function assertTenantId(Tenant $tenant, string $model, mixed $id, string $field): void
    {
        if ($id && ! $model::withoutTenantScope()->where('tenant_id', $tenant->id)->whereKey($id)->exists()) {
            abort(422, str($field)->replace('_', ' ')->headline() . ' must belong to this salon.');
        }
    }

    private function priorityFor(string $scope): int
    {
        return [
            CommissionRule::SCOPE_STAFF_SERVICE => 10,
            CommissionRule::SCOPE_STAFF_PRODUCT => 15,
            CommissionRule::SCOPE_STAFF => 30,
            CommissionRule::SCOPE_SERVICE => 40,
            CommissionRule::SCOPE_PRODUCT => 45,
            CommissionRule::SCOPE_BRANCH => 80,
            CommissionRule::SCOPE_TENANT => 100,
        ][$scope] ?? 100;
    }
}
