<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\StorePlanRequest;
use App\Http\Requests\Plan\UpdatePlanRequest;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\PlanEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlanManagementController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeSuperAdmin($request);

        $plans = Plan::query()
            ->withCount('subscriptions')
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('is_active', $request->string('status')->toString() === 'active');
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('price')
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.plan-management.plans.list', compact('plans'));
    }

    public function show(Request $request, Plan $plan, PlanEntitlementService $entitlements): View
    {
        $this->authorizeSuperAdmin($request);

        $plan->loadCount('subscriptions');

        $subscriptions = $plan->subscriptions()
            ->with('tenant')
            ->latest()
            ->limit(25)
            ->get();

        return view('pages/apps.plan-management.plans.show', [
            'plan' => $plan,
            'subscriptions' => $subscriptions,
            'entitlements' => $entitlements,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeSuperAdmin($request);

        return view('pages/apps.plan-management.plans.create', [
            'plan' => new Plan([
                'price' => 0,
                'billing_period' => 'monthly',
                'trial_days' => 0,
                'sort_order' => 0,
                'is_active' => true,
            ]),
            'billingPeriods' => Plan::BILLING_PERIODS,
            'featureOptions' => Plan::FEATURE_OPTIONS,
        ]);
    }

    public function store(StorePlanRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $plan = Plan::create($this->planPayload($validated));

        return redirect()
            ->route('plan-management.plans.show', $plan)
            ->with('status', 'Plan created successfully.');
    }

    public function edit(Request $request, Plan $plan): View
    {
        $this->authorizeSuperAdmin($request);

        return view('pages/apps.plan-management.plans.edit', [
            'plan' => $plan,
            'billingPeriods' => Plan::BILLING_PERIODS,
            'featureOptions' => Plan::FEATURE_OPTIONS,
        ]);
    }

    public function update(UpdatePlanRequest $request, Plan $plan): RedirectResponse
    {
        $plan->update($this->planPayload($request->validated(), $plan));

        return redirect()
            ->route('plan-management.plans.show', $plan)
            ->with('status', 'Plan updated successfully. Existing subscriptions keep their assigned snapshot until you change them.');
    }

    public function destroy(Request $request, Plan $plan): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $plan->update(['is_active' => false]);

        return redirect()
            ->route('plan-management.plans.index')
            ->with('status', 'Plan archived successfully. Existing subscriptions were not removed.');
    }

    public function updateStatus(Request $request, Plan $plan): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $plan->update(['is_active' => (bool) $validated['is_active']]);

        return back()->with('status', $plan->is_active ? 'Plan activated successfully.' : 'Plan deactivated successfully.');
    }

    public function subscriptions(Request $request): View
    {
        $this->authorizeSuperAdmin($request);

        $tenants = Tenant::query()
            ->with(['subscription.plan'])
            ->withCount(['branches', 'staff', 'users', 'customers'])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('pages/apps.plan-management.subscriptions.index', [
            'tenants' => $tenants,
            'plans' => Plan::active()->orderBy('sort_order')->orderBy('price')->get(),
            'statuses' => ['trialing', 'active', 'past_due', 'cancelled'],
        ]);
    }

    public function updateSubscription(Request $request, Tenant $tenant, PlanEntitlementService $entitlements): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $validated = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'status' => ['required', 'string', 'in:trialing,active,past_due,cancelled'],
        ]);

        $plan = Plan::query()->findOrFail($validated['plan_id']);

        if (! $plan->is_active && $tenant->subscription?->plan_id !== $plan->id) {
            return back()
                ->withErrors(['plan_id' => 'Only active plans can be assigned to a tenant.'])
                ->withInput();
        }

        $subscription = $entitlements->assignPlan($tenant, $plan, $validated['status']);

        if ($validated['status'] === 'cancelled') {
            $subscription->update([
                'cancelled_at' => now(),
                'ends_at' => now(),
            ]);
        }

        return back()->with('status', "Subscription updated for {$tenant->name}.");
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('Super Admin'), 403);
    }

    private function planPayload(array $validated, ?Plan $plan = null): array
    {
        $features = collect(Plan::FEATURE_OPTIONS)
            ->keys()
            ->mapWithKeys(fn (string $feature) => [$feature => in_array($feature, $validated['features'] ?? [], true)])
            ->all();

        $slug = $this->uniqueSlug(trim((string) ($validated['slug'] ?? '')) ?: Str::slug($validated['name']), $plan);

        return [
            'name' => $validated['name'],
            'slug' => $slug,
            'price' => $validated['price'],
            'billing_period' => $validated['billing_period'],
            'max_branches' => $validated['max_branches'] ?? null,
            'max_staff' => $validated['max_staff'] ?? null,
            'max_users' => $validated['max_users'] ?? null,
            'max_customers' => $validated['max_customers'] ?? null,
            'trial_days' => $validated['trial_days'] ?? 0,
            'sort_order' => $validated['sort_order'] ?? 0,
            'features' => $features,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'is_recommended' => (bool) ($validated['is_recommended'] ?? false),
        ];
    }

    private function uniqueSlug(string $slug, ?Plan $plan = null): string
    {
        $slug = $slug !== '' ? $slug : Str::random(8);
        $base = $slug;
        $suffix = 2;

        while (Plan::query()
            ->where('slug', $slug)
            ->when($plan, fn ($query) => $query->whereKeyNot($plan->id))
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
