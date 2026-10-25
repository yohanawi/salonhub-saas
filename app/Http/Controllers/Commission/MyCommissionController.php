<?php

namespace App\Http\Controllers\Commission;

use App\Http\Controllers\Controller;
use App\Models\CommissionPayout;
use App\Models\CommissionRule;
use App\Models\Staff;
use App\Models\StaffCommission;
use App\Services\Commission\TieredCommissionService;
use App\Services\PlanEntitlementService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * A staff member's own, live view of what they have earned: month totals by status, progress towards
 * the next commission tier, and every ledger line.
 */
class MyCommissionController extends Controller
{
    public function __invoke(Request $request, TieredCommissionService $tiered, PlanEntitlementService $entitlements): View
    {
        $user = $request->user();
        $staff = $user->staffProfile;

        abort_unless($user->can('commissions.view_own') || $user->can('commissions.view'), 403);

        if (! $staff) {
            return view('pages/apps.commission-management.my-commissions', ['staff' => null]);
        }

        $entitlements->ensureFeature($staff->tenant, 'staff_commissions');

        $timezone = $staff->tenant?->timezone ?: config('app.timezone');
        $month = $this->month($request, $timezone);
        $from = $month->startOfMonth();
        $to = $month->endOfMonth();

        $monthQuery = fn () => StaffCommission::withoutTenantScope()
            ->where('tenant_id', $staff->tenant_id)
            ->where('staff_id', $staff->id)
            ->where('status', '!=', StaffCommission::STATUS_CANCELLED)
            ->whereBetween('earned_at', [$from, $to]);

        // A plain status => total map (an Eloquent collection's only() would match primary keys instead).
        $byStatus = $monthQuery()
            ->selectRaw('status, SUM(commission_amount) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $sum = fn (array $statuses) => round((float) $byStatus->only($statuses)->sum(), 2);

        $sales = $tiered->monthlySales($staff, $month);

        return view('pages/apps.commission-management.my-commissions', [
            'staff' => $staff,
            'currency' => $staff->tenant?->currency ?: 'LKR',
            'month' => $month,
            'isCurrentMonth' => $month->isSameMonth(CarbonImmutable::now($timezone)),
            'totals' => [
                'net' => $sum(StaffCommission::STATUSES),
                'pending' => $sum([StaffCommission::STATUS_PENDING, StaffCommission::STATUS_EARNED]),
                'approved' => $sum([StaffCommission::STATUS_APPROVED]),
                'paid' => $sum([StaffCommission::STATUS_PAID]),
                'reversed' => $sum([StaffCommission::STATUS_REVERSED]),
            ],
            'sales' => $sales,
            'tierProgress' => $this->tierProgress($staff, $sales, $tiered, $month),
            'commissions' => $monthQuery()
                ->with(['invoice:id,invoice_number', 'service:id,name', 'product:id,name', 'customer:id,first_name,last_name'])
                ->latest('earned_at')
                ->latest('id')
                ->paginate(15)
                ->withQueryString(),
            'history' => $this->history($staff, $month),
            'awaitingPayout' => round((float) StaffCommission::withoutTenantScope()
                ->where('tenant_id', $staff->tenant_id)
                ->where('staff_id', $staff->id)
                // Approved but not yet paid out, across all months.
                ->where('status', StaffCommission::STATUS_APPROVED)
                ->sum('commission_amount'), 2),
            'lastPayout' => CommissionPayout::withoutTenantScope()
                ->where('tenant_id', $staff->tenant_id)
                ->where('staff_id', $staff->id)
                ->where('status', CommissionPayout::STATUS_PAID)
                ->latest('paid_at')
                ->first(),
        ]);
    }

    private function month(Request $request, string $timezone): CarbonImmutable
    {
        $now = CarbonImmutable::now($timezone);

        if ($request->filled('month') && preg_match('/^\d{4}-\d{2}$/', (string) $request->query('month'))) {
            $month = CarbonImmutable::createFromFormat('Y-m-d', $request->query('month') . '-01', $timezone)->startOfDay();

            return $month->greaterThan($now) ? $now->startOfMonth() : $month;
        }

        return $now->startOfMonth();
    }

    /**
     * Active tiered rules that can apply to this staff member's sales, with progress to the next tier.
     */
    private function tierProgress(Staff $staff, float $sales, TieredCommissionService $tiered, CarbonImmutable $month): Collection
    {
        $branchIds = $staff->branches()->pluck('branches.id')->all();

        return CommissionRule::withoutTenantScope()
            ->with(['service:id,name', 'product:id,name', 'branch:id,name'])
            ->where('tenant_id', $staff->tenant_id)
            ->where('commission_type', CommissionRule::TYPE_TIERED)
            ->activeForDate($month->endOfMonth()->min(now())->toDateString())
            ->where(function (Builder $query) use ($staff, $branchIds) {
                $query->whereIn('commission_scope', [CommissionRule::SCOPE_TENANT, CommissionRule::SCOPE_SERVICE, CommissionRule::SCOPE_PRODUCT])
                    ->orWhere(fn (Builder $query) => $query->whereIn('commission_scope', [CommissionRule::SCOPE_STAFF, CommissionRule::SCOPE_STAFF_SERVICE, CommissionRule::SCOPE_STAFF_PRODUCT])->where('staff_id', $staff->id))
                    ->orWhere(fn (Builder $query) => $query->where('commission_scope', CommissionRule::SCOPE_BRANCH)->whereIn('branch_id', $branchIds));
            })
            ->orderBy('priority')
            ->get()
            ->map(fn (CommissionRule $rule) => [
                'rule' => $rule,
                'label' => $this->ruleLabel($rule),
                'tiers' => $rule->sortedTiers(),
                'progress' => $tiered->progress($rule->sortedTiers(), $sales),
            ]);
    }

    private function ruleLabel(CommissionRule $rule): string
    {
        return match ($rule->commission_scope) {
            CommissionRule::SCOPE_SERVICE, CommissionRule::SCOPE_STAFF_SERVICE => 'Service: ' . ($rule->service?->name ?? 'Service'),
            CommissionRule::SCOPE_PRODUCT, CommissionRule::SCOPE_STAFF_PRODUCT => 'Product: ' . ($rule->product?->name ?? 'Product'),
            CommissionRule::SCOPE_BRANCH => 'Branch: ' . ($rule->branch?->name ?? 'Branch'),
            CommissionRule::SCOPE_STAFF => 'Your personal target',
            default => 'Salon-wide target',
        };
    }

    /**
     * Net commission for the six months up to the selected one.
     */
    private function history(Staff $staff, CarbonImmutable $month): Collection
    {
        return collect(range(5, 0))->map(function (int $back) use ($staff, $month) {
            $start = $month->subMonthsNoOverflow($back)->startOfMonth();

            return [
                'month' => $start,
                'total' => round((float) StaffCommission::withoutTenantScope()
                    ->where('tenant_id', $staff->tenant_id)
                    ->where('staff_id', $staff->id)
                    ->where('status', '!=', StaffCommission::STATUS_CANCELLED)
                    ->whereBetween('earned_at', [$start, $start->endOfMonth()])
                    ->sum('commission_amount'), 2),
            ];
        });
    }
}
