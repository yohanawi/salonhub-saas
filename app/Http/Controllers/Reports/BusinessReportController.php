<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Tenant;
use App\Services\BranchContext;
use App\Services\PlanEntitlementService;
use App\Services\Reports\BranchComparisonService;
use App\Services\Reports\ProfitLossService;
use App\Services\Reports\ReportExporter;
use App\Services\Reports\StaffPerformanceService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class BusinessReportController extends Controller
{
    public function profitLoss(Request $request, ProfitLossService $reports): View
    {
        [$tenant, $branches, $selected, $branchIds, $from, $to] = $this->scope($request, 'reports.financial', ['basic_reports', 'advanced_reports']);

        return view('pages/apps.reports.profit-loss', [
            'report' => $reports->build($tenant, $branchIds, $from, $to),
            'previous' => $reports->previous($tenant, $branchIds, $from, $to),
            'branches' => $branches,
            'selectedBranch' => $selected,
            'currency' => $tenant->currency ?: 'LKR',
        ]);
    }

    public function profitLossExport(Request $request, ProfitLossService $reports, ReportExporter $exporter): Response
    {
        [$tenant, , $selected, $branchIds, $from, $to] = $this->scope($request, 'reports.financial', ['basic_reports', 'advanced_reports']);

        return $exporter->download(
            $reports->document($tenant, $reports->build($tenant, $branchIds, $from, $to), $selected?->name ?? 'All branches'),
            $request->string('format', 'pdf')->toString()
        );
    }

    public function branchComparison(Request $request, BranchComparisonService $reports): View
    {
        [$tenant, $branches, , , $from, $to] = $this->scope($request, 'reports.financial', ['multi_branch_reports', 'advanced_reports']);

        return view('pages/apps.reports.branch-comparison', [
            'report' => $reports->build($tenant, $branches, $from, $to),
            'currency' => $tenant->currency ?: 'LKR',
        ]);
    }

    public function branchComparisonExport(Request $request, BranchComparisonService $reports, ReportExporter $exporter): Response
    {
        [$tenant, $branches, , , $from, $to] = $this->scope($request, 'reports.financial', ['multi_branch_reports', 'advanced_reports']);

        return $exporter->download($reports->document($tenant, $reports->build($tenant, $branches, $from, $to)), $request->string('format', 'pdf')->toString());
    }

    public function staffPerformance(Request $request, StaffPerformanceService $reports): View
    {
        [$tenant, $branches, $selected, $branchIds, $from, $to] = $this->scope($request, 'reports.staff', ['basic_reports', 'advanced_reports']);

        return view('pages/apps.reports.staff-performance', [
            'report' => $reports->build($tenant, $branchIds, $from, $to),
            'branches' => $branches,
            'selectedBranch' => $selected,
            'currency' => $tenant->currency ?: 'LKR',
        ]);
    }

    public function staffPerformanceExport(Request $request, StaffPerformanceService $reports, ReportExporter $exporter): Response
    {
        [$tenant, , $selected, $branchIds, $from, $to] = $this->scope($request, 'reports.staff', ['basic_reports', 'advanced_reports']);

        return $exporter->download(
            $reports->document($tenant, $reports->build($tenant, $branchIds, $from, $to), $selected?->name ?? 'All branches'),
            $request->string('format', 'pdf')->toString()
        );
    }

    /**
     * @return array{0: Tenant, 1: Collection<int, Branch>, 2: ?Branch, 3: array<int>, 4: CarbonImmutable, 5: CarbonImmutable}
     */
    private function scope(Request $request, string $permission, array $features): array
    {
        $user = $request->user();
        abort_unless($user->tenant_id && $user->can($permission), 403);

        $tenant = $user->tenant;
        $entitlements = app(PlanEntitlementService::class);
        abort_unless(collect($features)->contains(fn (string $feature) => $entitlements->featureEnabled($tenant, $feature)), 403, 'Your plan does not include this report.');

        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $branches = app(BranchContext::class)->availableBranches($user);
        abort_if($branches->isEmpty(), 403, 'You are not assigned to any active branch.');

        $selected = $request->filled('branch_id') ? $branches->firstWhere('id', $request->integer('branch_id')) : null;
        abort_if($request->filled('branch_id') && ! $selected, 403);

        $timezone = $tenant->timezone ?: config('app.timezone');
        $now = CarbonImmutable::now($timezone);
        $from = $request->filled('from') ? CarbonImmutable::createFromFormat('Y-m-d', $request->string('from')->toString(), $timezone)->startOfDay() : $now->startOfMonth();
        $to = $request->filled('to') ? CarbonImmutable::createFromFormat('Y-m-d', $request->string('to')->toString(), $timezone)->endOfDay() : $now->endOfDay();

        return [$tenant, $branches, $selected, $selected ? [$selected->id] : $branches->pluck('id')->all(), $from, $to];
    }
}
