<?php

namespace App\Services\Reports;

use App\Models\Appointment;
use App\Models\AppointmentFeedback;
use App\Models\Invoice;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\StaffCommission;
use App\Models\StaffTip;
use App\Models\Tenant;
use App\Support\Reports\ReportDocument;
use App\Support\Reports\ReportTable;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Per-staff results for a period: work done, money brought in, client loyalty (rebooking), ratings and attendance.
 */
class StaffPerformanceService
{
    /** A completed visit counts as rebooked if the client has another booking within this many days. */
    public const REBOOK_WINDOW_DAYS = 60;

    /**
     * @param  array<int>  $branchIds
     */
    public function build(Tenant $tenant, array $branchIds, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $staffMembers = Staff::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->when($branchIds !== [], fn ($query) => $query->whereHas('branches', fn ($branches) => $branches->whereIn('branches.id', $branchIds)))
            ->orderBy('first_name')
            ->get();

        $sales = $this->sales($tenant, $branchIds, $from, $to);
        $visits = $this->visits($tenant, $branchIds, $from, $to);
        $ratings = AppointmentFeedback::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->when($branchIds !== [], fn ($query) => $query->whereIn('branch_id', $branchIds))
            ->whereNotNull('rating')
            ->whereBetween('submitted_at', [$from, $to])
            ->groupBy('staff_id')
            ->selectRaw('staff_id, AVG(rating) as average, COUNT(*) as total')
            ->get()
            ->keyBy('staff_id');
        $commissions = StaffCommission::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->when($branchIds !== [], fn ($query) => $query->whereIn('branch_id', $branchIds))
            ->where('status', '!=', StaffCommission::STATUS_CANCELLED)
            ->whereBetween('earned_at', [$from, $to])
            ->groupBy('staff_id')
            ->selectRaw('staff_id, SUM(commission_amount) as total')
            ->pluck('total', 'staff_id');
        $tips = StaffTip::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->when($branchIds !== [], fn ($query) => $query->whereIn('branch_id', $branchIds))
            ->whereBetween('earned_at', [$from, $to])
            ->groupBy('staff_id')
            ->selectRaw('staff_id, SUM(amount) as total')
            ->pluck('total', 'staff_id');
        $attendance = StaffAttendance::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('staff_id')
            ->selectRaw("staff_id, SUM(CASE WHEN status IN ('present', 'late') THEN 1 ELSE 0 END) as days, SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days, SUM(COALESCE(worked_minutes, 0)) as minutes")
            ->get()
            ->keyBy('staff_id');

        $rows = $staffMembers->map(function (Staff $staff) use ($sales, $visits, $ratings, $commissions, $tips, $attendance) {
            $sale = $sales->get($staff->id);
            $visit = $visits->get($staff->id);
            $serviceRevenue = round((float) ($sale->service_revenue ?? 0), 2);
            $productRevenue = round((float) ($sale->product_revenue ?? 0), 2);
            $invoices = (int) ($sale->invoices ?? 0);
            $completed = (int) ($visit['completed'] ?? 0);

            return [
                'staff' => $staff,
                'services' => (int) ($sale->services ?? 0),
                'service_revenue' => $serviceRevenue,
                'product_revenue' => $productRevenue,
                'total_revenue' => round($serviceRevenue + $productRevenue, 2),
                'invoices' => $invoices,
                'average_bill' => $invoices > 0 ? round(($serviceRevenue + $productRevenue) / $invoices, 2) : 0.0,
                'clients' => (int) ($sale->clients ?? 0),
                'completed' => $completed,
                'no_shows' => (int) ($visit['no_shows'] ?? 0),
                'cancelled' => (int) ($visit['cancelled'] ?? 0),
                'rebooked' => (int) ($visit['rebooked'] ?? 0),
                'rebooking_rate' => $completed > 0 ? round(($visit['rebooked'] ?? 0) / $completed * 100, 1) : null,
                'rating' => isset($ratings[$staff->id]) ? round((float) $ratings[$staff->id]->average, 1) : null,
                'rating_count' => (int) ($ratings[$staff->id]->total ?? 0),
                'commission' => round((float) ($commissions[$staff->id] ?? 0), 2),
                'tips' => round((float) ($tips[$staff->id] ?? 0), 2),
                'days_worked' => (int) ($attendance[$staff->id]->days ?? 0),
                'late_days' => (int) ($attendance[$staff->id]->late_days ?? 0),
                'hours_worked' => round(((int) ($attendance[$staff->id]->minutes ?? 0)) / 60, 1),
            ];
        })
            ->filter(fn (array $row) => $row['services'] > 0 || $row['completed'] > 0 || $row['days_worked'] > 0 || $row['product_revenue'] > 0)
            ->sortByDesc('total_revenue')
            ->values();

        return [
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'totals' => [
                'revenue' => round((float) $rows->sum('total_revenue'), 2),
                'services' => (int) $rows->sum('services'),
                'completed' => (int) $rows->sum('completed'),
                'rebooking_rate' => $rows->sum('completed') > 0 ? round($rows->sum('rebooked') / $rows->sum('completed') * 100, 1) : null,
                'rating' => $rows->sum('rating_count') > 0
                    ? round($rows->sum(fn ($row) => ($row['rating'] ?? 0) * $row['rating_count']) / $rows->sum('rating_count'), 1)
                    : null,
            ],
        ];
    }

    public function document(Tenant $tenant, array $report, string $branchLabel): ReportDocument
    {
        $currency = $tenant->currency ?: 'LKR';
        $document = new ReportDocument('Staff Performance', $tenant->name, [
            'Period' => $report['from']->format('M d, Y') . ' – ' . $report['to']->format('M d, Y'),
            'Branch' => $branchLabel,
        ], [
            'Revenue' => $currency . ' ' . number_format($report['totals']['revenue'], 2),
            'Services performed' => number_format($report['totals']['services']),
            'Rebooking rate' => $report['totals']['rebooking_rate'] === null ? '—' : $report['totals']['rebooking_rate'] . '%',
            'Average rating' => $report['totals']['rating'] === null ? '—' : $report['totals']['rating'] . ' / 5',
        ], $currency);

        return $document->table(new ReportTable('Staff', [
            'Staff', 'Services', 'Service Revenue', 'Product Revenue', 'Average Bill', 'Clients', 'Completed Visits', 'Rebooking %', 'Rating', 'No-shows', 'Commission', 'Tips', 'Days Worked', 'Late Days', 'Hours',
        ], $report['rows']->map(fn ($row) => [
            $row['staff']->full_name, $row['services'], $row['service_revenue'], $row['product_revenue'], $row['average_bill'], $row['clients'], $row['completed'],
            $row['rebooking_rate'] ?? '—', $row['rating'] ? "{$row['rating']} ({$row['rating_count']})" : '—', $row['no_shows'], $row['commission'], $row['tips'],
            $row['days_worked'], $row['late_days'], $row['hours_worked'],
        ]), [2, 3, 4, 10, 11]));
    }

    private function sales(Tenant $tenant, array $branchIds, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.tenant_id', $tenant->id)
            ->when($branchIds !== [], fn ($query) => $query->whereIn('sales.branch_id', $branchIds))
            ->where('sales.status', Invoice::STATUS_ISSUED)
            ->whereBetween('sales.issued_at', [$from, $to])
            ->whereNotNull('sale_items.staff_id')
            ->whereIn('sale_items.item_type', ['service', 'product'])
            ->groupBy('sale_items.staff_id')
            ->selectRaw("sale_items.staff_id,
                SUM(CASE WHEN sale_items.item_type = 'service' THEN sale_items.quantity ELSE 0 END) as services,
                SUM(CASE WHEN sale_items.item_type = 'service' THEN sale_items.gross_amount - sale_items.discount_amount ELSE 0 END) as service_revenue,
                SUM(CASE WHEN sale_items.item_type = 'product' THEN sale_items.gross_amount - sale_items.discount_amount ELSE 0 END) as product_revenue,
                COUNT(DISTINCT sales.id) as invoices,
                COUNT(DISTINCT sales.customer_id) as clients")
            ->get()
            ->keyBy('staff_id');
    }

    /**
     * Appointment outcomes per lead staff member, and how many completed visits led to another booking.
     */
    private function visits(Tenant $tenant, array $branchIds, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $window = self::REBOOK_WINDOW_DAYS;
        $driver = DB::connection()->getDriverName();
        $plusWindow = $driver === 'pgsql'
            ? "a.starts_at + interval '{$window} days'"
            : ($driver === 'sqlite' ? "datetime(a.starts_at, '+{$window} days')" : "DATE_ADD(a.starts_at, INTERVAL {$window} DAY)");

        return collect(DB::select("
            SELECT a.staff_id,
                SUM(CASE WHEN a.status = ? THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN a.status = ? THEN 1 ELSE 0 END) AS no_shows,
                SUM(CASE WHEN a.status = ? THEN 1 ELSE 0 END) AS cancelled,
                SUM(CASE WHEN a.status = ? AND EXISTS (
                    SELECT 1 FROM appointments nxt
                    WHERE nxt.tenant_id = a.tenant_id
                      AND nxt.customer_id = a.customer_id
                      AND nxt.id <> a.id
                      AND nxt.status NOT IN (?, ?)
                      AND nxt.starts_at > a.starts_at
                      AND nxt.starts_at <= {$plusWindow}
                ) THEN 1 ELSE 0 END) AS rebooked
            FROM appointments a
            WHERE a.tenant_id = ?
              AND a.staff_id IS NOT NULL
              AND a.starts_at BETWEEN ? AND ?
              " . ($branchIds !== [] ? 'AND a.branch_id IN (' . implode(',', array_map('intval', $branchIds)) . ')' : '') . "
            GROUP BY a.staff_id
        ", [
            Appointment::STATUS_COMPLETED,
            Appointment::STATUS_NO_SHOW,
            Appointment::STATUS_CANCELLED,
            Appointment::STATUS_COMPLETED,
            Appointment::STATUS_CANCELLED,
            Appointment::STATUS_NO_SHOW,
            $tenant->id,
            $from->toDateTimeString(),
            $to->toDateTimeString(),
        ]))->mapWithKeys(fn ($row) => [(int) $row->staff_id => (array) $row]);
    }
}
