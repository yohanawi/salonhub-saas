<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Billing\Concerns\ResolvesReportScope;
use App\Http\Controllers\Controller;
use App\Services\BranchContext;
use App\Services\PlanEntitlementService;
use App\Services\Reports\TaxReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaxReportController extends Controller
{
    use ResolvesReportScope;

    public function index(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements, TaxReportService $reports): View
    {
        [$tenant, $branches, $selectedBranch, $branchIds] = $this->reportScope($request, $branchContext, $entitlements);

        return view('pages/apps.billing.reports.tax', [
            'report' => $reports->build($tenant, $branchIds, $this->month($request, $tenant->timezone ?: config('app.timezone'))),
            'branches' => $branches,
            'selectedBranch' => $selectedBranch,
            'tenant' => $tenant,
        ]);
    }

    public function export(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements, TaxReportService $reports): StreamedResponse
    {
        [$tenant, , $selectedBranch, $branchIds] = $this->reportScope($request, $branchContext, $entitlements);
        $report = $reports->build($tenant, $branchIds, $this->month($request, $tenant->timezone ?: config('app.timezone')));

        $rows = $report['invoices']->map(fn ($row) => [
            $row['invoice_number'],
            $row['issued_at']->format('Y-m-d H:i'),
            $row['taxable'],
            $row['exempt'],
            $row['tax'],
            round($row['net'] + $row['tax'], 2),
        ]);

        return $this->csv(
            'tax-' . $report['month']->format('Y-m') . ($selectedBranch ? '-' . str($selectedBranch->name)->slug() : '') . '.csv',
            ['Invoice', 'Date', 'Taxable Value', 'Exempt Value', 'Tax', 'Total'],
            $rows
        );
    }

    private function month(Request $request, string $timezone): CarbonImmutable
    {
        $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        // Default to last month: that is the return being prepared.
        return $request->filled('month')
            ? CarbonImmutable::createFromFormat('Y-m-d', $request->string('month')->toString() . '-01', $timezone)->startOfDay()
            : CarbonImmutable::now($timezone)->subMonthNoOverflow()->startOfMonth();
    }
}
