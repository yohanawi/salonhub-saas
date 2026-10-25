<?php

namespace App\Http\Controllers\CustomerManagement;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerCampaign;
use App\Services\Customers\CampaignService;
use App\Services\Customers\CustomerSegmentService;
use App\Services\PlanEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerSegmentController extends Controller
{
    public function index(Request $request, CustomerSegmentService $segments, CampaignService $campaigns, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', Customer::class);
        $tenant = $request->user()->tenant;
        abort_unless($tenant, 403);
        $entitlements->ensureFeature($tenant, 'customer_management');

        return view('pages/apps.customer-management.segments', [
            'segments' => collect(CustomerSegmentService::SEGMENTS)->map(fn (array $meta, string $key) => $meta + [
                'key' => $key,
                'description' => $segments->describe($tenant, $key),
                'count' => $segments->query($tenant, $key)->count(),
                'reachable' => $campaigns->recipients($tenant, $key)->count(),
            ]),
            'campaigns' => CustomerCampaign::withoutTenantScope()->with('createdBy')->where('tenant_id', $tenant->id)->latest()->limit(10)->get(),
            'topReferrers' => Customer::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->whereHas('referrals')
                ->withCount('referrals')
                ->orderByDesc('referrals_count')
                ->limit(10)
                ->get(),
            'canSend' => $request->user()->can('customer.campaign'),
        ]);
    }

    public function sendCampaign(Request $request, CampaignService $campaigns): RedirectResponse
    {
        abort_unless($request->user()->tenant_id && $request->user()->can('customer.campaign'), 403);

        $data = $request->validate([
            'segment' => ['required', Rule::in(array_keys(CustomerSegmentService::SEGMENTS))],
            'name' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:640'],
        ]);

        $campaign = $campaigns->send($request->user()->tenant, $data['segment'], $data['name'], $data['message'], $request->user());

        return back()->with('status', "Campaign \"{$campaign->name}\" queued for {$campaign->queued_count} customers.");
    }
}
