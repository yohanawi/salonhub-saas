<?php

namespace App\Services\Reports;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\Tenant;
use App\Services\Billing\DayEndReportService;
use App\Support\Reports\ReportDocument;
use App\Support\Reports\ReportTable;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Builds exportable documents for the reports that can be emailed on a schedule, and for the billing
 * reports' Excel/PDF downloads.
 */
class ReportCatalog
{
    public const SCHEDULABLE = [
        'profit_loss' => 'Profit & Loss',
        'sales' => 'Sales Summary',
        'branch_comparison' => 'Branch Comparison',
        'staff_performance' => 'Staff Performance',
        'expenses' => 'Expenses',
        'day_end' => 'Day-End Summary',
    ];

    /** Permission needed to receive / schedule each report. */
    public const PERMISSIONS = [
        'profit_loss' => 'reports.financial',
        'sales' => 'billing.view_reports',
        'branch_comparison' => 'reports.financial',
        'staff_performance' => 'reports.staff',
        'expenses' => 'expense_reports.view',
        'day_end' => 'billing.view_reports',
    ];

    public function __construct(
        private readonly ProfitLossService $profitLoss,
        private readonly BranchComparisonService $branchComparison,
        private readonly StaffPerformanceService $staffPerformance,
        private readonly SalesReportService $sales,
        private readonly DayEndReportService $dayEnd,
    ) {
    }

    /**
     * @param  Collection<int, Branch>  $branches  every branch the report covers
     */
    public function document(string $report, Tenant $tenant, Collection $branches, ?Branch $branch, CarbonImmutable $from, CarbonImmutable $to): ReportDocument
    {
        $branchIds = $branch ? [$branch->id] : $branches->pluck('id')->all();
        $label = $branch?->name ?? 'All branches';

        return match ($report) {
            'profit_loss' => $this->profitLoss->document($tenant, $this->profitLoss->build($tenant, $branchIds, $from, $to), $label),
            'branch_comparison' => $this->branchComparison->document($tenant, $this->branchComparison->build($tenant, $branches, $from, $to)),
            'staff_performance' => $this->staffPerformance->document($tenant, $this->staffPerformance->build($tenant, $branchIds, $from, $to), $label),
            'sales' => $this->salesDocument($tenant, $this->sales->build($tenant, $branchIds, $from, $to), $label),
            'expenses' => $this->expensesDocument($tenant, $branchIds, $from, $to, $label),
            'day_end' => $this->dayEndDocument($tenant, $this->dayEnd->build($tenant, $branchIds, $from->startOfDay()), $label),
            default => abort(404),
        };
    }

    public function salesDocument(Tenant $tenant, array $report, string $branchLabel): ReportDocument
    {
        $currency = $tenant->currency ?: 'LKR';
        $money = fn ($value) => $currency . ' ' . number_format((float) $value, 2);
        $summary = $report['summary'];

        $document = new ReportDocument('Sales Report', $tenant->name, [
            'Period' => $report['from']->format('M d, Y') . ' – ' . $report['to']->format('M d, Y'),
            'Branch' => $branchLabel,
        ], [
            'Invoices' => number_format($summary['invoices']),
            'Net sales' => $money($summary['net_sales']),
            'Discounts' => $money($summary['discounts']),
            'Tax' => $money($summary['tax']),
            'Tips' => $money($summary['tips']),
            'Refunds' => $money($summary['refunds']),
            'Gift cards sold' => $money($summary['gift_cards_sold']),
        ], $currency);

        $document->table(new ReportTable('By Month', ['Month', 'Invoices', 'Services', 'Products', 'Packages & Memberships', 'Net Sales', 'Tax', 'Tips', 'Refunds'],
            collect($report['months'])->map(fn ($row) => [$row['label'], $row['invoices'], $row['services'], $row['products'], $row['other'], $row['net_sales'], $row['tax'], $row['tips'], $row['refunds']]),
            [2, 3, 4, 5, 6, 7, 8]));
        $document->table(new ReportTable('By Staff', ['Staff', 'Services Done', 'Service Sales', 'Product Sales', 'Total Sales', 'Tips', 'Commission'],
            collect($report['staff'])->map(fn ($row) => [$row['name'], $row['services_count'], $row['service_sales'], $row['product_sales'], $row['total_sales'], $row['tips'], $row['commission']]),
            [2, 3, 4, 5, 6]));
        $document->table(new ReportTable('Services', ['Service', 'Quantity', 'Paid by Package', 'Gross', 'Net Sales', 'Average'],
            collect($report['services'])->map(fn ($row) => [$row['name'], $row['quantity'], $row['package_quantity'], $row['gross'], $row['net'], $row['average']]),
            [3, 4, 5]));
        $document->table(new ReportTable('Products', ['Product', 'Quantity', 'Gross', 'Net Sales', 'Average'],
            collect($report['products'])->map(fn ($row) => [$row['name'], $row['quantity'], $row['gross'], $row['net'], $row['average']]),
            [2, 3, 4]));

        return $document->table(new ReportTable('Payments', ['Payment Method', 'Transactions', 'Amount'],
            collect($report['payments'])->map(fn ($row) => [$row['name'], $row['count'], $row['total']]),
            [2]));
    }

    public function dayEndDocument(Tenant $tenant, array $report, string $branchLabel): ReportDocument
    {
        $currency = $tenant->currency ?: 'LKR';
        $money = fn ($value) => $currency . ' ' . number_format((float) $value, 2);
        $sales = $report['sales'];

        $document = new ReportDocument('Day-End Report', $tenant->name, [
            'Date' => $report['day']->format('l, M d, Y'),
            'Branch' => $branchLabel,
        ], [
            'Invoices' => number_format($sales['invoices']),
            'Sales total' => $money($sales['total']),
            'Collected' => $money($report['collected']),
            'Refunded' => $money($report['refunded']),
            'Net collected' => $money($report['net_collected']),
            'Tips' => $money($sales['tips']),
            'Cash difference' => $money($report['cash_difference']),
        ], $currency);

        $document->table(new ReportTable('Sales Breakdown', ['Line', 'Amount'], [
            ['Subtotal', $sales['subtotal']],
            ['Discounts', -$sales['discount']],
            ['Loyalty redemptions', -$sales['loyalty_redemption']],
            ['Tax', $sales['tax']],
            ['Total', $sales['total']],
            ['Voided invoices (' . $report['voided']['invoices'] . ')', $report['voided']['total']],
            ['Expenses paid from the drawer', $report['drawer_expenses']],
        ], [1]));
        $document->table(new ReportTable('Payments Received', ['Method', 'Transactions', 'Amount'],
            collect($report['payments'])->map(fn ($row) => [$row['name'], $row['count'], $row['total']]), [2]));

        return $document->table(new ReportTable('Refunds Paid', ['Method', 'Refunds', 'Amount'],
            collect($report['refunds'])->map(fn ($row) => [$row['name'], $row['count'], $row['total']]), [2]));
    }

    private function expensesDocument(Tenant $tenant, array $branchIds, CarbonImmutable $from, CarbonImmutable $to, string $branchLabel): ReportDocument
    {
        $base = Expense::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->whereIn('branch_id', $branchIds)
            ->where('expense_status', '!=', Expense::STATUS_CANCELLED)
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()]);

        return $this->expenseSummary($tenant, [
            'totalExpenses' => (clone $base)->sum('total_amount'),
            'totalPaid' => (clone $base)->sum('paid_amount'),
            'totalOutstanding' => (clone $base)->sum('balance_amount'),
            'byCategory' => (clone $base)->selectRaw('category_id, sum(total_amount) as total')->with('category')->groupBy('category_id')->orderByDesc('total')->get(),
            'byBranch' => (clone $base)->selectRaw('branch_id, sum(total_amount) as total')->with('branch')->groupBy('branch_id')->orderByDesc('total')->get(),
            'byVendor' => (clone $base)->selectRaw('vendor_id, sum(total_amount) as total')->with('vendor')->groupBy('vendor_id')->orderByDesc('total')->get(),
        ], ['Period' => $from->format('M d, Y') . ' – ' . $to->format('M d, Y'), 'Branch' => $branchLabel]);
    }

    /**
     * Shared by the scheduled report and the Expense Reports page download.
     */
    public function expenseSummary(?Tenant $tenant, array $data, array $meta): ReportDocument
    {
        $currency = $tenant?->currency ?: 'LKR';
        $money = fn ($value) => $currency . ' ' . number_format((float) $value, 2);

        $document = new ReportDocument('Expense Report', $tenant?->name ?? 'All salons', $meta, [
            'Total expenses' => $money($data['totalExpenses']),
            'Paid' => $money($data['totalPaid']),
            'Outstanding' => $money($data['totalOutstanding']),
        ], $currency);

        $document->table(new ReportTable('By Category', ['Category', 'Total'], collect($data['byCategory'])->map(fn ($row) => [$row->category?->name ?? 'Uncategorised', $row->total]), [1]));
        $document->table(new ReportTable('By Branch', ['Branch', 'Total'], collect($data['byBranch'])->map(fn ($row) => [$row->branch?->name ?? '—', $row->total]), [1]));

        return $document->table(new ReportTable('By Vendor', ['Vendor', 'Total'], collect($data['byVendor'])->map(fn ($row) => [$row->vendor?->name ?? 'No vendor', $row->total]), [1]));
    }
}
