<?php

namespace App\Http\Controllers\Loyalty;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\Loyalty\CustomerRewardsService;
use App\Services\PlanEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerRewardsController extends Controller
{
    /**
     * Customer-facing rewards page. No login: the "signed" middleware already verified the expiring URL.
     */
    public function show(int $customer, CustomerRewardsService $rewards): View
    {
        $customer = Customer::withoutTenantScope()->with('tenant')->findOrFail($customer);

        return view('pages/apps.loyalty-management.rewards-portal', $rewards->summary($customer) + [
            'currency' => $customer->tenant?->currency ?: 'LKR',
        ]);
    }

    public function sendBalance(Request $request, Customer $customer, CustomerRewardsService $rewards, PlanEntitlementService $entitlements): RedirectResponse
    {
        $this->authorize('view', $customer);
        abort_unless($request->user()->can('loyalty.view'), 403);
        $entitlements->ensureFeature($customer->tenant, 'loyalty_membership');

        $log = $rewards->sendBalance($customer, $request->user());

        return back()->with(
            $log ? 'status' : 'error',
            $log ? 'Points balance sent to ' . $customer->phone . '.' : 'Could not send: the customer needs a phone number and a loyalty account, and SMS/WhatsApp must be set up in Settings.'
        );
    }
}
