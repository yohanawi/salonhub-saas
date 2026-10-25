<?php

namespace App\Services\Reports;

use App\Models\Appointment;
use App\Models\AppointmentFeedback;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Support\Reports\ReportDocument;
use App\Support\Reports\ReportTable;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Branches side by side: money (from the P&L) and day-to-day activity for the same period.
 */
class BranchComparisonService
{
    public function __construct(private readonly ProfitLossService $profitLoss)
    {
    }

    /**
     * @param  Collection<int, Branch>  $branches
     */
    public function build(Tenant $tenant, Collection $branches, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = $branches->map(function (Branch $branch) use ($tenant, $from, $to) {
            $pl = $this->profitLoss->build($tenant, [$branch->id], $from, $to);

            $invoices = Invoice::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->where('branch_id', $branch->id)
                ->where('status', Invoice::STATUS_ISSUED)
                ->whereBetween('issued_at', [$from, $to])
                ->count();

            $appointments = Appointment::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->where('branch_id', $branch->id)
                ->whereBetween('starts_at', [$from, $to])
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $booked = (int) $appointments->sum();
            $noShows = (int) ($appointments[Appointment::STATUS_NO_SHOW] ?? 0);

            $topService = DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sales.tenant_id', $tenant->id)
                ->where('sales.branch_id', $branch->id)
                ->where('sales.status', Invoice::STATUS_ISSUED)
                ->whereBetween('sales.issued_at', [$from, $to])
                ->where('sale_items.item_type', 'service')
                ->groupBy('sale_items.item_name')
                ->selectRaw('sale_items.item_name as name, SUM(sale_items.quantity) as quantity')
                ->orderByDesc('quantity')
                ->first();

            $ratings = AppointmentFeedback::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->where('branch_id', $branch->id)
                ->whereNotNull('rating')
                ->whereBetween('submitted_at', [$from, $to])
                ->selectRaw('AVG(rating) as average, COUNT(*) as total')
                ->first();

            return [
                'branch' => $branch,
                'net_revenue' => $pl['net_revenue'],
                'gross_profit' => $pl['gross_profit'],
                'expenses' => $pl['expense_total'],
                'staff_cost' => $pl['staff']['total'],
                'net_profit' => $pl['net_profit'],
                'net_margin' => $pl['net_margin'],
                'invoices' => $invoices,
                'average_bill' => $invoices > 0 ? round($pl['revenue'] / $invoices, 2) : 0.0,
                'appointments' => $booked,
                'completed' => (int) ($appointments[Appointment::STATUS_COMPLETED] ?? 0),
                'no_show_rate' => $booked > 0 ? round($noShows / $booked * 100, 1) : 0.0,
                'cancelled' => (int) ($appointments[Appointment::STATUS_CANCELLED] ?? 0),
                'new_customers' => Customer::withoutTenantScope()->where('tenant_id', $tenant->id)->where('branch_id', $branch->id)->whereBetween('created_at', [$from, $to])->count(),
                'top_service' => $topService?->name,
                'rating' => $ratings?->total ? round((float) $ratings->average, 1) : null,
                'rating_count' => (int) ($ratings?->total ?? 0),
            ];
        })->values();

        $best = fn (string $key) => $rows->sortByDesc($key)->first()['branch']->id ?? null;

        return [
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'leaders' => [
                'net_revenue' => $best('net_revenue'),
                'net_profit' => $best('net_profit'),
                'average_bill' => $best('average_bill'),
                'appointments' => $best('appointments'),
            ],
            'max' => [
                'net_revenue' => max(1, (float) $rows->max('net_revenue')),
                'net_profit' => max(1, (float) $rows->max(fn ($row) => abs($row['net_profit']))),
            ],
        ];
    }

    public function document(Tenant $tenant, array $report): ReportDocument
    {
        $currency = $tenant->currency ?: 'LKR';
        $rows = $report['rows'];

        $document = new ReportDocument('Branch Comparison', $tenant->name, [
            'Period' => $report['from']->format('M d, Y') . ' – ' . $report['to']->format('M d, Y'),
            'Branches' => (string) $rows->count(),
        ], [
            'Total net revenue' => $currency . ' ' . number_format((float) $rows->sum('net_revenue'), 2),
            'Total net profit' => $currency . ' ' . number_format((float) $rows->sum('net_profit'), 2),
            'Appointments' => number_format($rows->sum('appointments')),
            'Invoices' => number_format($rows->sum('invoices')),
        ], $currency);

        $document->table(new ReportTable('Financials', ['Branch', 'Net Revenue', 'Gross Profit', 'Expenses', 'Staff Cost', 'Net Profit', 'Net Margin %'],
            $rows->map(fn ($row) => [$row['branch']->name, $row['net_revenue'], $row['gross_profit'], $row['expenses'], $row['staff_cost'], $row['net_profit'], $row['net_margin']]),
            [1, 2, 3, 4, 5],
            ['Total', $rows->sum('net_revenue'), $rows->sum('gross_profit'), $rows->sum('expenses'), $rows->sum('staff_cost'), $rows->sum('net_profit'), '']));

        $document->table(new ReportTable('Activity', ['Branch', 'Invoices', 'Average Bill', 'Appointments', 'Completed', 'No-show %', 'New Customers', 'Rating', 'Top Service'],
            $rows->map(fn ($row) => [$row['branch']->name, $row['invoices'], $row['average_bill'], $row['appointments'], $row['completed'], $row['no_show_rate'], $row['new_customers'], $row['rating'] ?? '—', $row['top_service'] ?? '—']),
            [2]));

        return $document;
    }
}
