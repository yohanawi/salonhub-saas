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
use App\Services\Reports\ReportCatalog;
use App\Services\Reports\ReportExporter;
use Symfony\Component\HttpFoundation\Response;
use App\Support\Reports\ReportDocument;
use App\Support\Reports\ReportTable;
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

    public function download(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements, TaxReportService $reports, ReportExporter $exporter): Response
    {
        [$tenant, , $selectedBranch, $branchIds] = $this->reportScope($request, $branchContext, $entitlements);
        $report = $reports->build($tenant, $branchIds, $this->month($request, $tenant->timezone ?: config('app.timezone')));
        $currency = $tenant->currency ?: 'LKR';

        $document = new ReportDocument('Tax Report', $tenant->name, array_filter([
            'Month' => $report['month']->format('F Y'),
            'Branch' => $selectedBranch?->name ?? 'All branches',
            'Tax number' => $report['tax_number'],
        ]), [
            'Taxable value' => $currency . ' ' . number_format((float) $report['totals']['taxable'], 2),
            'Tax' => $currency . ' ' . number_format((float) $report['totals']['tax'], 2),
            'Exempt value' => $currency . ' ' . number_format((float) $report['exempt']['net'], 2),
            'Invoices' => number_format($report['counts']['invoices']),
        ], $currency);

        $document->table(new ReportTable('Invoices', ['Invoice', 'Date', 'Taxable Value', 'Exempt Value', 'Tax', 'Total'],
            $report['invoices']->map(fn ($row) => [$row['invoice_number'], $row['issued_at']->format('Y-m-d H:i'), $row['taxable'], $row['exempt'], $row['tax'], round($row['net'] + $row['tax'], 2)]),
            [2, 3, 4, 5],
            ['Total', '', $report['invoices']->sum('taxable'), $report['invoices']->sum('exempt'), $report['invoices']->sum('tax'), round($report['invoices']->sum('net') + $report['invoices']->sum('tax'), 2)]));

        return $exporter->download($document, $request->string('format', 'xlsx')->toString());
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
