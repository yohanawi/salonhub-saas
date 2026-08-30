<?php

namespace App\Http\Controllers\Commission;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commission\UpdateCommissionSettingRequest;
use App\Models\CommissionSetting;
use App\Models\Tenant;
use App\Services\Commission\CommissionSettingsService;
use App\Services\PlanEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommissionSettingController extends Controller
{
    public function edit(Request $request, CommissionSettingsService $settings, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', CommissionSetting::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'staff_commissions');
        }

        $setting = $tenant ? $settings->ensure($tenant) : null;

        return view('pages/apps.commission-management.settings.edit', [
            'setting' => $setting,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'selectedTenant' => $tenant,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function update(UpdateCommissionSettingRequest $request, CommissionSetting $setting, PlanEntitlementService $entitlements): RedirectResponse
    {
        $entitlements->ensureFeature($setting->tenant, 'staff_commissions');

        $data = $request->validated();
        $setting->update([
            'commission_enabled' => (bool) ($data['commission_enabled'] ?? false),
            'default_service_commission_type' => $data['default_service_commission_type'],
            'default_service_commission_value' => $data['default_service_commission_value'],
            'default_product_commission_type' => $data['default_product_commission_type'],
            'default_product_commission_value' => $data['default_product_commission_value'],
            'calculation_basis' => $data['calculation_basis'],
            'earn_trigger' => $data['earn_trigger'],
            'requires_approval' => (bool) ($data['requires_approval'] ?? false),
            'allow_manual_adjustment' => (bool) ($data['allow_manual_adjustment'] ?? false),
            'allow_negative_commission' => (bool) ($data['allow_negative_commission'] ?? false),
            'refund_behavior' => $data['refund_behavior'],
        ]);

        return redirect()->route('commission-management.settings.edit', ['tenant_id' => $setting->tenant_id])->with('status', 'Commission settings updated successfully.');
    }

    private function tenantContext(Request $request): array
    {
        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : ($isSuperAdmin ? null : $request->user()->tenant);

        return [$isSuperAdmin, $tenant];
    }
}
