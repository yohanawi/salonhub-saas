<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlanManagementController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeSuperAdmin($request);

        $plans = Plan::query()
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return view('pages/apps.plan-management.plans.list', compact('plans'));
    }

    public function create(Request $request): View
    {
        $this->authorizeSuperAdmin($request);

        return view('pages/apps.plan-management.plans.create', [
            'featureOptions' => Plan::FEATURE_OPTIONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('plans', 'slug')],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'billing_period' => ['required', Rule::in(['trial', 'monthly', 'yearly'])],
            'max_branches' => ['nullable', 'integer', 'min:1'],
            'max_staff' => ['nullable', 'integer', 'min:1'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'max_customers' => ['nullable', 'integer', 'min:1'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', Rule::in(array_keys(Plan::FEATURE_OPTIONS))],
            'is_active' => ['nullable', 'boolean'],
            'is_recommended' => ['nullable', 'boolean'],
        ]);

        $selectedFeatures = collect($validated['features'] ?? [])
            ->mapWithKeys(fn (string $feature) => [$feature => true])
            ->all();

        $slug = trim((string) ($validated['slug'] ?? '')) ?: Str::slug($validated['name']);

        if (Plan::where('slug', $slug)->exists()) {
            return back()
                ->withErrors(['slug' => 'A plan with this slug already exists.'])
                ->withInput();
        }

        Plan::create([
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
            'features' => $selectedFeatures,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'is_recommended' => (bool) ($validated['is_recommended'] ?? false),
        ]);

        return redirect()
            ->route('plan-management.plans.index')
            ->with('status', 'Plan created successfully.');
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('Super Admin'), 403);
    }
}
