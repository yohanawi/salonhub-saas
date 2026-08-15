<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\PlanEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ServiceStatusController extends Controller
{
    public function update(Request $request, Service $service, PlanEntitlementService $entitlements): RedirectResponse
    {
        $this->authorize('changeStatus', $service);

        $entitlements->ensureFeature($service->tenant, 'services');

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $service->update(['is_active' => (bool) $validated['is_active']]);

        return back()->with('status', $service->is_active ? 'Service activated successfully.' : 'Service deactivated successfully.');
    }
}
