<?php

namespace App\Http\Controllers\Promotions;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\PromotionUsage;
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

class PromotionReportController extends Controller
{
    public function index(Request $request, PlanEntitlementService $entitlements): View|Response
    {
        abort_unless($request->user()->hasRole('Super Admin') || $request->user()->can('promotion_reports.view'), 403);

        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'promotions_discounts');
        }

        $usages = PromotionUsage::withoutTenantScope()
            ->with(['promotion', 'branch'])
            ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))
            ->where('status', PromotionUsage::STATUS_USED)
            ->when($request->filled('from'), fn (Builder $query) => $query->whereDate('used_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn (Builder $query) => $query->whereDate('used_at', '<=', $request->date('to')));

        $data = [
            'totalRedemptions' => (clone $usages)->count(),
            'discountGiven' => (clone $usages)->sum('discount_amount'),
            'byPromotion' => (clone $usages)
                ->selectRaw('promotion_id, count(*) as redemptions, sum(discount_amount) as discount_given')
                ->groupBy('promotion_id')
                ->with('promotion')
                ->orderByDesc('discount_given')
                ->take(20)
                ->get(),
            'activePromotions' => Promotion::withoutTenantScope()->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id))->where('status', Promotion::STATUS_ACTIVE)->count(),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ];

        if ($request->filled('format')) {
            return app(ReportExporter::class)->download($this->document($data, $tenant, $request), $request->string('format')->toString());
        }

        return view('pages/apps.promotions.reports.index', $data);
    }

    private function tenantContext(Request $request): array
    {
        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id') ? Tenant::query()->findOrFail($request->integer('tenant_id')) : ($isSuperAdmin ? null : $request->user()->tenant);

        return [$isSuperAdmin, $tenant];
    }

    private function document(array $data, ?Tenant $tenant, Request $request): ReportDocument
    {
        $currency = $tenant?->currency ?: 'LKR';

        $document = new ReportDocument('Promotions Report', $tenant?->name ?? 'All salons', array_filter([
            'From' => $request->input('from'),
            'To' => $request->input('to'),
        ]), [
            'Redemptions' => number_format((int) $data['totalRedemptions']),
            'Discount given' => $currency . ' ' . number_format((float) $data['discountGiven'], 2),
            'Active promotions' => number_format((int) $data['activePromotions']),
        ], $currency);

        return $document->table(new ReportTable('By Promotion', ['Promotion', 'Redemptions', 'Discount Given'],
            $data['byPromotion']->map(fn ($row) => [$row->promotion?->name ?? '—', $row->redemptions, $row->discount_given]), [2]));
    }
}
