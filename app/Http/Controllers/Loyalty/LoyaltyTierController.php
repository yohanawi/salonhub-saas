<?php

namespace App\Http\Controllers\Loyalty;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyTier;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Loyalty\LoyaltyTierService;
use App\Services\PlanEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LoyaltyTierController extends Controller
{
    public function index(Request $request, LoyaltyService $loyalty, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', LoyaltyProgram::class);
        $tenant = $request->user()->tenant;
        abort_unless($tenant, 403);
        $entitlements->ensureFeature($tenant, 'loyalty_membership');

        $program = $loyalty->activeProgram($tenant);

        return view('pages/apps.loyalty-management.tiers.index', [
            'program' => $program,
            'tiers' => $program
                ? LoyaltyTier::withoutTenantScope()->where('loyalty_program_id', $program->id)->withCount('accounts')->orderBy('min_points')->get()
                : collect(),
            'colors' => LoyaltyTier::COLORS,
        ]);
    }

    public function store(Request $request, LoyaltyService $loyalty, LoyaltyTierService $tiers): RedirectResponse
    {
        $tenant = $request->user()->tenant;
        abort_unless($tenant, 403);
        $program = $loyalty->activeProgram($tenant);
        abort_unless($program, 404);
        $this->authorize('update', $program);

        LoyaltyTier::withoutTenantScope()->create($this->validated($request, $program) + [
            'tenant_id' => $tenant->id,
            'loyalty_program_id' => $program->id,
        ]);

        $moved = $tiers->refreshProgram($program);

        return back()->with('status', "Tier added. {$moved} " . str('member')->plural($moved) . ' re-placed.');
    }

    public function update(Request $request, LoyaltyTier $tier, LoyaltyTierService $tiers): RedirectResponse
    {
        $this->authorize('update', $tier->program);

        $tier->update($this->validated($request, $tier->program, $tier));
        $moved = $tiers->refreshProgram($tier->program);

        return back()->with('status', "Tier updated. {$moved} " . str('member')->plural($moved) . ' re-placed.');
    }

    public function destroy(LoyaltyTier $tier, LoyaltyTierService $tiers): RedirectResponse
    {
        $this->authorize('update', $tier->program);
        $program = $tier->program;

        $tier->delete();
        $tiers->refreshProgram($program);

        return back()->with('status', 'Tier removed. Members were moved to the tier matching their points.');
    }

    private function validated(Request $request, LoyaltyProgram $program, ?LoyaltyTier $tier = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('loyalty_tiers', 'name')->where('loyalty_program_id', $program->id)->ignore($tier?->id)],
            'min_points' => ['required', 'integer', 'min:0', 'max:100000000', Rule::unique('loyalty_tiers', 'min_points')->where('loyalty_program_id', $program->id)->ignore($tier?->id)],
            'points_multiplier' => ['required', 'numeric', 'min:1', 'max:10'],
            'color' => ['required', Rule::in(LoyaltyTier::COLORS)],
            'benefits' => ['nullable', 'string', 'max:1000'],
        ], [
            'min_points.unique' => 'Another tier already starts at this number of points.',
        ]);
    }
}
