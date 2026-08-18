<?php

namespace App\Http\Controllers\Loyalty;

use App\Http\Controllers\Controller;
use App\Http\Requests\Loyalty\AdjustPointsRequest;
use App\Models\Customer;
use App\Models\LoyaltyPointTransaction;
use App\Models\Tenant;
use App\Services\Loyalty\LoyaltyService;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoyaltyPointTransactionController extends Controller
{
    public function index(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', LoyaltyPointTransaction::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'loyalty_membership');
        }

        $transactions = (LoyaltyPointTransaction::withoutTenantScope())
            ->with(['tenant', 'customer', 'source'])
            ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))
            ->when($request->filled('customer_id'), fn (Builder $query) => $query->where('customer_id', $request->integer('customer_id')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('pages/apps.loyalty-management.transactions.index', [
            'transactions' => $transactions,
            'customers' => $tenant ? Customer::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('first_name')->get() : collect(),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function adjust(AdjustPointsRequest $request, LoyaltyService $loyalty, PlanEntitlementService $entitlements): RedirectResponse
    {
        $tenant = $this->tenantForWrite($request);
        $entitlements->ensureFeature($tenant, 'loyalty_membership');
        $customer = Customer::withoutTenantScope()->where('tenant_id', $tenant->id)->findOrFail($request->integer('customer_id'));
        $loyalty->adjust($customer, (int) $request->integer('points'), $request->string('reason')->toString(), $request->user());

        return back()->with('status', 'Customer points adjusted successfully.');
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
