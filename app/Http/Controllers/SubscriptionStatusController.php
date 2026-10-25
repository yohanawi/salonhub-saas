<?php

namespace App\Http\Controllers;

use App\Services\PlanEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionStatusController extends Controller
{
    public function inactive(Request $request, PlanEntitlementService $entitlements): View|RedirectResponse
    {
        $tenant = $request->user()->tenant;
        $reason = $tenant ? $entitlements->subscriptionBlockedReason($tenant) : null;

        if ($reason === null) {
            return redirect()->route('dashboard');
        }

        return view('pages/system.subscription-inactive', [
            'tenant' => $tenant,
            'subscription' => $tenant->subscription()->with('plan')->first(),
            'reason' => $reason,
        ]);
    }
}
