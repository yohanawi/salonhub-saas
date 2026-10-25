<?php

namespace App\Http\Controllers\Loyalty;

use App\Http\Controllers\Controller;
use App\Models\CustomerLoyaltyAccount;
use App\Models\CustomerMembership;
use App\Models\LoyaltyPointTransaction;
use App\Models\MembershipBenefitUsage;
use App\Models\Tenant;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\Reports\ReportCatalog;
use App\Services\Reports\ReportExporter;
use App\Support\Reports\ReportDocument;
use App\Support\Reports\ReportTable;
use Symfony\Component\HttpFoundation\Response;

class LoyaltyReportController extends Controller
{
    public function index(Request $request, PlanEntitlementService $entitlements): View|Response
    {
        abort_unless($request->user()->hasRole('Super Admin') || $request->user()->can('loyalty_reports.view'), 403);
        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id') ? Tenant::query()->findOrFail($request->integer('tenant_id')) : ($isSuperAdmin ? null : $request->user()->tenant);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'loyalty_membership');
        }

        $transactionBase = LoyaltyPointTransaction::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id));
        $membershipBase = CustomerMembership::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id));

        $data = [
            'pointsEarned' => (clone $transactionBase)->where('points', '>', 0)->sum('points'),
            'pointsRedeemed' => abs((clone $transactionBase)->where('type', LoyaltyPointTransaction::TYPE_REDEEM)->sum('points')),
            'pointsExpired' => abs((clone $transactionBase)->where('type', LoyaltyPointTransaction::TYPE_EXPIRE)->sum('points')),
            'outstandingPoints' => CustomerLoyaltyAccount::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))->sum('available_points'),
            'membershipRevenue' => (clone $membershipBase)->sum('price_paid'),
            'activeMemberships' => (clone $membershipBase)->where('status', CustomerMembership::STATUS_ACTIVE)->count(),
            'benefitDiscounts' => MembershipBenefitUsage::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))->sum('discount_amount'),
            'topBalances' => CustomerLoyaltyAccount::withoutTenantScope()->with('customer')->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))->orderByDesc('available_points')->take(10)->get(),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ];

        if ($request->filled('format')) {
            return app(ReportExporter::class)->download($this->document($data, $tenant, $request), $request->string('format')->toString());
        }

        return view('pages/apps.loyalty-management.reports', $data);
    }

    private function document(array $data, ?Tenant $tenant, Request $request): ReportDocument
    {
        $currency = $tenant?->currency ?: 'LKR';

        $document = new ReportDocument('Loyalty & Membership Report', $tenant?->name ?? 'All salons', [], [
            'Points earned' => number_format((int) $data['pointsEarned']),
            'Points redeemed' => number_format((int) $data['pointsRedeemed']),
            'Points expired' => number_format((int) $data['pointsExpired']),
            'Outstanding points' => number_format((int) $data['outstandingPoints']),
            'Membership revenue' => $currency . ' ' . number_format((float) $data['membershipRevenue'], 2),
            'Active memberships' => number_format((int) $data['activeMemberships']),
            'Member discounts given' => $currency . ' ' . number_format((float) $data['benefitDiscounts'], 2),
        ], $currency);

        return $document->table(new ReportTable('Top Point Balances', ['Customer', 'Available Points', 'Lifetime Earned'],
            $data['topBalances']->map(fn ($account) => [$account->customer?->full_name ?? '—', $account->available_points, $account->lifetime_earned_points])));
    }
}
