<?php

namespace App\Http\Controllers\Promotions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotions\StorePromotionCouponRequest;
use App\Http\Requests\Promotions\UpdatePromotionCouponRequest;
use App\Models\Promotion;
use App\Models\PromotionCoupon;
use App\Models\Tenant;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionCouponController extends Controller
{
    public function index(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', PromotionCoupon::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'promotions_discounts');
        }

        $coupons = ($isSuperAdmin ? PromotionCoupon::withoutTenantScope() : PromotionCoupon::query()->where('tenant_id', $tenant->id))
            ->with(['tenant', 'promotion'])
            ->withCount('usages')
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where('code', 'like', "%{$search}%")
                    ->orWhereHas('promotion', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.promotions.coupons.index', [
            'coupons' => $coupons,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function create(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('create', PromotionCoupon::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'promotions_discounts');
        }

        return view('pages/apps.promotions.coupons.create', $this->formData($tenant, $isSuperAdmin) + [
            'coupon' => new PromotionCoupon(['status' => PromotionCoupon::STATUS_ACTIVE]),
            'selectedTenant' => $tenant,
        ]);
    }

    public function store(StorePromotionCouponRequest $request, PlanEntitlementService $entitlements): RedirectResponse
    {
        $tenant = $this->tenantForWrite($request);
        $entitlements->ensureFeature($tenant, 'promotions_discounts');

        $coupon = PromotionCoupon::create($this->payload($request->validated(), $tenant->id) + [
            'code' => strtoupper(trim($request->validated('code'))),
            'created_by' => $request->user()->id,
        ]);

        $coupon->promotion()->update([
            'application_type' => Promotion::APPLICATION_COUPON,
            'coupon_required' => true,
        ]);

        return redirect()->route('promotions.coupons.index')->with('status', 'Coupon saved successfully.');
    }

    public function edit(PromotionCoupon $coupon, PlanEntitlementService $entitlements): View
    {
        $this->authorize('update', $coupon);
        $entitlements->ensureFeature($coupon->tenant, 'promotions_discounts');

        return view('pages/apps.promotions.coupons.edit', $this->formData($coupon->tenant, false) + [
            'coupon' => $coupon,
            'selectedTenant' => $coupon->tenant,
            'isSuperAdmin' => false,
        ]);
    }

    public function update(UpdatePromotionCouponRequest $request, PromotionCoupon $coupon, PlanEntitlementService $entitlements): RedirectResponse
    {
        $this->authorize('update', $coupon);
        $entitlements->ensureFeature($coupon->tenant, 'promotions_discounts');
        $coupon->update($this->payload($request->validated(), $coupon->tenant_id) + ['code' => strtoupper(trim($request->validated('code')))]);

        return redirect()->route('promotions.coupons.index')->with('status', 'Coupon updated successfully.');
    }

    private function payload(array $data, int $tenantId): array
    {
        return [
            'tenant_id' => $tenantId,
            'promotion_id' => $data['promotion_id'],
            'starts_at' => $data['starts_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'usage_limit' => $data['usage_limit'] ?? null,
            'per_customer_limit' => $data['per_customer_limit'] ?? null,
            'status' => $data['status'],
        ];
    }

    private function formData(?Tenant $tenant, bool $isSuperAdmin): array
    {
        $tenantId = $tenant?->id;

        return [
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
            'promotions' => Promotion::withoutTenantScope()
                ->when($tenantId, fn (Builder $query) => $query->where('tenant_id', $tenantId))
                ->whereIn('status', [Promotion::STATUS_ACTIVE, Promotion::STATUS_DRAFT])
                ->orderBy('name')
                ->get(),
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
