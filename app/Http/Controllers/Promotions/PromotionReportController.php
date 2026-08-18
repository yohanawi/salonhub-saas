<?php

namespace App\Http\Controllers\Promotions;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\PromotionUsage;
use App\Models\Tenant;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionReportController extends Controller
{
    public function index(Request $request, PlanEntitlementService $entitlements): View
    {
        abort_unless($request->user()->hasRole('Super Admin') || $request->user()->can('promotion_reports.view'), 403);

        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'promotions_discounts');
        }

        $usages = PromotionUsage::withoutTenantScope()
            ->with(['promotion', 'branch'])
            ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))
            ->where('status', PromotionUsage::STATUS_USED)
            ->when($request->filled('from'), fn (Builder $query) => $query->whereDate('used_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn (Builder $query) => $query->whereDate('used_at', '<=', $request->date('to')));

        return view('pages/apps.promotions.reports.index', [
            'totalRedemptions' => (clone $usages)->count(),
            'discountGiven' => (clone $usages)->sum('discount_amount'),
            'byPromotion' => (clone $usages)
                ->selectRaw('promotion_id, count(*) as redemptions, sum(discount_amount) as discount_given')
                ->groupBy('promotion_id')
                ->with('promotion')
                ->orderByDesc('discount_given')
                ->take(20)
                ->get(),
            'activePromotions' => Promotion::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))->where('status', Promotion::STATUS_ACTIVE)->count(),
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
