<?php

namespace App\Http\Controllers\Promotions;

use App\Http\Controllers\Controller;
use App\Models\PromotionUsage;
use App\Models\Tenant;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionUsageController extends Controller
{
    public function index(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', PromotionUsage::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'promotions_discounts');
        }

        $usages = ($isSuperAdmin ? PromotionUsage::withoutTenantScope() : PromotionUsage::query()->where('tenant_id', $tenant->id))
            ->with(['tenant', 'promotion', 'coupon', 'customer', 'branch', 'invoice', 'createdBy'])
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->latest('used_at')
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.promotions.usages.index', [
            'usages' => $usages,
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
