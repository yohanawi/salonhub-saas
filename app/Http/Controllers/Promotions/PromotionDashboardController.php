<?php

namespace App\Http\Controllers\Promotions;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\PromotionCoupon;
use App\Models\PromotionUsage;
use App\Models\Tenant;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionDashboardController extends Controller
{
    public function __invoke(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', Promotion::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'promotions_discounts');
        }

        $promotions = Promotion::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id));
        $usages = PromotionUsage::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id));

        return view('pages/apps.promotions.dashboard', [
            'activePromotions' => (clone $promotions)->where('status', Promotion::STATUS_ACTIVE)->count(),
            'scheduledPromotions' => (clone $promotions)->where('status', Promotion::STATUS_ACTIVE)->where('starts_at', '>', now())->count(),
            'coupons' => PromotionCoupon::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))->active()->count(),
            'redemptions' => (clone $usages)->where('status', PromotionUsage::STATUS_USED)->count(),
            'discountGiven' => (clone $usages)->where('status', PromotionUsage::STATUS_USED)->sum('discount_amount'),
            'recentUsages' => (clone $usages)->with(['promotion', 'coupon', 'customer', 'branch', 'invoice'])->latest('used_at')->take(8)->get(),
            'topPromotions' => (clone $promotions)->withCount(['usages' => fn ($query) => $query->where('status', PromotionUsage::STATUS_USED)])->orderByDesc('usages_count')->take(8)->get(),
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
