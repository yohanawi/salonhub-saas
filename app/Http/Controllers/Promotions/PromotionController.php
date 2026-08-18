<?php

namespace App\Http\Controllers\Promotions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotions\StorePromotionRequest;
use App\Http\Requests\Promotions\UpdatePromotionRequest;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\MembershipPlan;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Service;
use App\Models\Tenant;
use App\Services\PlanEntitlementService;
use App\Services\Promotions\PromotionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function index(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', Promotion::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'promotions_discounts');
        }

        $promotions = ($isSuperAdmin ? Promotion::withoutTenantScope() : Promotion::query()->where('tenant_id', $tenant->id))
            ->with(['tenant', 'coupons'])
            ->withCount('usages')
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('application_type'), fn (Builder $query) => $query->where('application_type', $request->string('application_type')->toString()))
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(fn (Builder $query) => $query->where('name', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.promotions.promotions.index', [
            'promotions' => $promotions,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function create(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('create', Promotion::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'promotions_discounts');
        }

        return view('pages/apps.promotions.promotions.create', $this->formData($tenant, $isSuperAdmin) + [
            'promotion' => new Promotion([
                'promotion_type' => 'standard',
                'discount_type' => Promotion::DISCOUNT_PERCENTAGE,
                'application_type' => Promotion::APPLICATION_AUTOMATIC,
                'customer_scope' => Promotion::CUSTOMER_ALL,
                'branch_scope' => Promotion::BRANCH_ALL,
                'target_scope' => Promotion::TARGET_INVOICE,
                'starts_at' => now(),
                'status' => Promotion::STATUS_ACTIVE,
                'priority' => 100,
            ]),
            'selectedTenant' => $tenant,
        ]);
    }

    public function store(StorePromotionRequest $request, PlanEntitlementService $entitlements, PromotionService $promotions): RedirectResponse
    {
        $tenant = $this->tenantForWrite($request);
        $entitlements->ensureFeature($tenant, 'promotions_discounts');

        $promotion = Promotion::create($this->payload($request->validated(), $tenant->id) + [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $promotions->syncTargets($promotion, $request->validated());

        return redirect()->route('promotions.promotions.show', $promotion)->with('status', 'Promotion saved successfully.');
    }

    public function show(Promotion $promotion, PlanEntitlementService $entitlements): View
    {
        $this->authorize('view', $promotion);
        $entitlements->ensureFeature($promotion->tenant, 'promotions_discounts');

        $promotion->load(['tenant', 'branches', 'services', 'products', 'customers', 'membershipPlans', 'coupons', 'usages.customer', 'usages.branch', 'usages.invoice']);

        return view('pages/apps.promotions.promotions.show', [
            'promotion' => $promotion,
        ]);
    }

    public function edit(Promotion $promotion, PlanEntitlementService $entitlements): View
    {
        $this->authorize('update', $promotion);
        $entitlements->ensureFeature($promotion->tenant, 'promotions_discounts');

        $promotion->load(['branches', 'services', 'products', 'customers', 'membershipPlans']);

        return view('pages/apps.promotions.promotions.edit', $this->formData($promotion->tenant, false) + [
            'promotion' => $promotion,
            'selectedTenant' => $promotion->tenant,
            'isSuperAdmin' => false,
        ]);
    }

    public function update(UpdatePromotionRequest $request, Promotion $promotion, PlanEntitlementService $entitlements, PromotionService $promotions): RedirectResponse
    {
        $this->authorize('update', $promotion);
        $entitlements->ensureFeature($promotion->tenant, 'promotions_discounts');

        $promotion->update($this->payload($request->validated(), $promotion->tenant_id) + ['updated_by' => $request->user()->id]);
        $promotions->syncTargets($promotion, $request->validated());

        return redirect()->route('promotions.promotions.show', $promotion)->with('status', 'Promotion updated successfully.');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $this->authorize('delete', $promotion);
        $promotion->update(['status' => Promotion::STATUS_ARCHIVED]);
        $promotion->delete();

        return redirect()->route('promotions.promotions.index')->with('status', 'Promotion archived successfully.');
    }

    public function activate(Request $request, Promotion $promotion): RedirectResponse
    {
        $this->authorize('update', $promotion);
        abort_unless($request->user()->hasRole('Super Admin') || $request->user()->can('promotions.activate'), 403);
        $promotion->update(['status' => Promotion::STATUS_ACTIVE, 'updated_by' => $request->user()->id]);

        return back()->with('status', 'Promotion activated successfully.');
    }

    public function deactivate(Request $request, Promotion $promotion): RedirectResponse
    {
        $this->authorize('update', $promotion);
        abort_unless($request->user()->hasRole('Super Admin') || $request->user()->can('promotions.deactivate'), 403);
        $promotion->update(['status' => Promotion::STATUS_INACTIVE, 'updated_by' => $request->user()->id]);

        return back()->with('status', 'Promotion deactivated successfully.');
    }

    private function payload(array $data, int $tenantId): array
    {
        return [
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'internal_notes' => $data['internal_notes'] ?? null,
            'promotion_type' => $data['promotion_type'] ?? 'standard',
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'maximum_discount_amount' => $data['maximum_discount_amount'] ?? null,
            'application_type' => $data['application_type'],
            'coupon_required' => $data['application_type'] === Promotion::APPLICATION_COUPON,
            'minimum_spend' => $data['minimum_spend'] ?? 0,
            'minimum_quantity' => $data['minimum_quantity'] ?? null,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?? null,
            'usage_limit' => $data['usage_limit'] ?? null,
            'per_customer_limit' => $data['per_customer_limit'] ?? null,
            'customer_scope' => $data['customer_scope'],
            'branch_scope' => $data['branch_scope'],
            'target_scope' => $data['target_scope'],
            'is_stackable' => (bool) ($data['is_stackable'] ?? false),
            'priority' => $data['priority'] ?? 100,
            'status' => $data['status'],
        ];
    }

    private function formData(?Tenant $tenant, bool $isSuperAdmin): array
    {
        $tenantId = $tenant?->id;
        $scope = fn ($model) => $model::withoutTenantScope()->when($tenantId, fn (Builder $query) => $query->where('tenant_id', $tenantId))->orderBy('name')->get();

        return [
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
            'branches' => $scope(Branch::class),
            'services' => $scope(Service::class),
            'products' => $scope(Product::class),
            'customers' => Customer::withoutTenantScope()->when($tenantId, fn (Builder $query) => $query->where('tenant_id', $tenantId))->orderBy('first_name')->orderBy('last_name')->get(),
            'membershipPlans' => $scope(MembershipPlan::class),
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
