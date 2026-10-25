<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Billing\Concerns\ResolvesReportScope;
use App\Http\Controllers\Controller;
use App\Services\BranchContext;
use App\Services\PlanEntitlementService;
use App\Services\Reports\SalesReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Services\Reports\ReportCatalog;
use App\Services\Reports\ReportExporter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesReportController extends Controller
{
    use ResolvesReportScope;

    public const EXPORTS = ['months', 'staff', 'services', 'products', 'payments'];

    public function index(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements, SalesReportService $reports): View
    {
        [$tenant, $branches, $selectedBranch, $branchIds] = $this->reportScope($request, $branchContext, $entitlements);
        [$from, $to] = $this->period($request, $reports->timezone($tenant));

        return view('pages/apps.billing.reports.sales', [
            'report' => $reports->build($tenant, $branchIds, $from, $to),
            'branches' => $branches,
            'selectedBranch' => $selectedBranch,
            'tenant' => $tenant,
        ]);
    }

    public function export(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements, SalesReportService $reports): StreamedResponse
    {
        [$tenant, , $selectedBranch, $branchIds] = $this->reportScope($request, $branchContext, $entitlements);
        [$from, $to] = $this->period($request, $reports->timezone($tenant));
        $type = $request->string('type')->toString();
        abort_unless(in_array($type, self::EXPORTS, true), 404);

        $report = $reports->build($tenant, $branchIds, $from, $to);
        $filename = 'sales-' . $type . '-' . $from->format('Ymd') . '-' . $to->format('Ymd') . ($selectedBranch ? '-' . str($selectedBranch->name)->slug() : '') . '.csv';

        [$headers, $rows] = match ($type) {
            'months' => [
                ['Month', 'Invoices', 'Services', 'Products', 'Packages & Memberships', 'Net Sales', 'Tax', 'Tips', 'Refunds'],
                $report['months']->map(fn ($row) => [$row['label'], $row['invoices'], $row['services'], $row['products'], $row['other'], $row['net_sales'], $row['tax'], $row['tips'], $row['refunds']]),
            ],
            'staff' => [
                ['Staff', 'Services Done', 'Service Sales', 'Product Sales', 'Total Sales', 'Tips', 'Commission'],
                $report['staff']->map(fn ($row) => [$row['name'], $row['services_count'], $row['service_sales'], $row['product_sales'], $row['total_sales'], $row['tips'], $row['commission']]),
            ],
            'services' => [
                ['Service', 'Quantity', 'Paid by Package', 'Gross', 'Net Sales', 'Average'],
                $report['services']->map(fn ($row) => [$row['name'], $row['quantity'], $row['package_quantity'], $row['gross'], $row['net'], $row['average']]),
            ],
            'products' => [
                ['Product', 'Quantity', 'Gross', 'Net Sales', 'Average'],
                $report['products']->map(fn ($row) => [$row['name'], $row['quantity'], $row['gross'], $row['net'], $row['average']]),
            ],
            'payments' => [
                ['Payment Method', 'Transactions', 'Amount'],
                $report['payments']->map(fn ($row) => [$row['name'], $row['count'], $row['total']]),
            ],
        };

        return $this->csv($filename, $headers, $rows);
    }

    public function download(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements, SalesReportService $reports, ReportCatalog $catalog, ReportExporter $exporter): Response
    {
        [$tenant, , $selectedBranch, $branchIds] = $this->reportScope($request, $branchContext, $entitlements);
        [$from, $to] = $this->period($request, $reports->timezone($tenant));

        return $exporter->download(
            $catalog->salesDocument($tenant, $reports->build($tenant, $branchIds, $from, $to), $selectedBranch?->name ?? 'All branches'),
            $request->string('format', 'xlsx')->toString()
        );
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function period(Request $request, string $timezone): array
    {
        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $today = CarbonImmutable::now($timezone)->startOfDay();
        $from = $request->filled('from') ? CarbonImmutable::createFromFormat('Y-m-d', $request->string('from')->toString(), $timezone)->startOfDay() : $today->startOfMonth();
        $to = $request->filled('to') ? CarbonImmutable::createFromFormat('Y-m-d', $request->string('to')->toString(), $timezone)->startOfDay() : $today;

        if ($to->lt($from)) {
            throw ValidationException::withMessages(['to' => 'The end date must be on or after the start date.']);
        }

        if ($from->diffInDays($to) > 366) {
            throw ValidationException::withMessages(['from' => 'Choose a period of one year or less.']);
        }

        return [$from, $to];
    }
}
