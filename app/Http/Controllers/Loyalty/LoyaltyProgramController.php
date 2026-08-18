<?php

namespace App\Http\Controllers\Loyalty;

use App\Http\Controllers\Controller;
use App\Http\Requests\Loyalty\StoreLoyaltyProgramRequest;
use App\Http\Requests\Loyalty\UpdateLoyaltyProgramRequest;
use App\Models\LoyaltyProgram;
use App\Models\Tenant;
use App\Services\Loyalty\LoyaltyService;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoyaltyProgramController extends Controller
{
    public function index(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', LoyaltyProgram::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'loyalty_membership');
        }

        $programs = ($isSuperAdmin ? LoyaltyProgram::withoutTenantScope() : LoyaltyProgram::query()->where('tenant_id', $tenant->id))
            ->with('tenant')
            ->withCount(['rules', 'accounts'])
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.loyalty-management.programs.index', [
            'programs' => $programs,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function create(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('create', LoyaltyProgram::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'loyalty_membership');
        }

        return view('pages/apps.loyalty-management.programs.create', [
            'program' => new LoyaltyProgram([
                'status' => LoyaltyProgram::STATUS_ACTIVE,
                'points_expiry_days' => 365,
                'minimum_redeem_points' => 100,
                'maximum_redeem_percentage' => 30,
                'redemption_points' => 100,
                'redemption_value' => 500,
                'allow_partial_redemption' => true,
            ]),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'selectedTenant' => $tenant,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function store(StoreLoyaltyProgramRequest $request, PlanEntitlementService $entitlements, LoyaltyService $loyalty): RedirectResponse
    {
        $tenant = $this->tenantForWrite($request);
        $entitlements->ensureFeature($tenant, 'loyalty_membership');
        $program = LoyaltyProgram::create($this->payload($request->validated(), $tenant->id) + ['created_by' => $request->user()->id]);
        $loyalty->ensureDefaultRule($program);

        return redirect()->route('loyalty-management.programs.index')->with('status', 'Loyalty program saved successfully.');
    }

    public function edit(LoyaltyProgram $program, PlanEntitlementService $entitlements): View
    {
        $this->authorize('update', $program);
        $entitlements->ensureFeature($program->tenant, 'loyalty_membership');

        return view('pages/apps.loyalty-management.programs.edit', [
            'program' => $program,
            'tenants' => collect(),
            'selectedTenant' => $program->tenant,
            'isSuperAdmin' => false,
        ]);
    }

    public function update(UpdateLoyaltyProgramRequest $request, LoyaltyProgram $program, PlanEntitlementService $entitlements): RedirectResponse
    {
        $entitlements->ensureFeature($program->tenant, 'loyalty_membership');
        $program->update($this->payload($request->validated(), $program->tenant_id));

        return redirect()->route('loyalty-management.programs.index')->with('status', 'Loyalty program updated successfully.');
    }

    private function payload(array $data, int $tenantId): array
    {
        return [
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'],
            'points_expiry_days' => $data['points_expiry_days'] ?? null,
            'minimum_redeem_points' => $data['minimum_redeem_points'] ?? 0,
            'maximum_redeem_percentage' => $data['maximum_redeem_percentage'] ?? 30,
            'redemption_points' => $data['redemption_points'],
            'redemption_value' => $data['redemption_value'],
            'allow_partial_redemption' => (bool) ($data['allow_partial_redemption'] ?? false),
            'allow_points_on_discounted_sales' => (bool) ($data['allow_points_on_discounted_sales'] ?? false),
        ];
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
