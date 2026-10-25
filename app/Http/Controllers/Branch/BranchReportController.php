<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Inventory;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Refund;
use App\Models\Sale;
use App\Services\PlanEntitlementService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\Reports\ReportCatalog;
use App\Services\Reports\ReportExporter;
use Symfony\Component\HttpFoundation\Response;
use App\Support\Reports\ReportDocument;
use App\Support\Reports\ReportTable;

class BranchReportController extends Controller
{
    public function show(Request $request, Branch $branch, PlanEntitlementService $entitlements): View|Response
    {
        $this->authorize('viewReports', $branch);

        if (! $entitlements->featureEnabled($branch->tenant, 'basic_reports')
            && ! $entitlements->featureEnabled($branch->tenant, 'advanced_reports')
            && ! $entitlements->featureEnabled($branch->tenant, 'multi_branch_reports')) {
            abort(403);
        }

        $startDate = $request->date('start_date') ?: now()->startOfMonth();
        $endDate = $request->date('end_date') ?: now()->endOfMonth();

        $appointmentQuery = Appointment::query()
            ->where('branch_id', $branch->id)
            ->whereBetween('starts_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()]);

        $saleQuery = Sale::query()
            ->where('branch_id', $branch->id)
            ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()]);

        $purchaseQuery = Purchase::query()
            ->where('branch_id', $branch->id)
            ->whereBetween('purchase_date', [$startDate->toDateString(), $endDate->toDateString()]);

        $expenseQuery = Expense::query()
            ->where('branch_id', $branch->id)
            ->whereBetween('expense_date', [$startDate->toDateString(), $endDate->toDateString()]);

        // Net collections: void payments are excluded and refunds paid out in the period are taken off.
        $paymentTotal = (float) Payment::query()
            ->where('status', Payment::STATUS_COMPLETED)
            ->whereHas('sale', fn ($query) => $query->where('branch_id', $branch->id))
            ->whereBetween('paid_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->sum('amount')
            - (float) Refund::query()
                ->where('branch_id', $branch->id)
                ->where('status', Refund::STATUS_COMPLETED)
                ->whereBetween('processed_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
                ->sum('amount');

        $metrics = [
            'appointments' => (clone $appointmentQuery)->count(),
            'upcoming_appointments' => (clone $appointmentQuery)->where('starts_at', '>=', now())->count(),
            'sales' => (clone $saleQuery)->count(),
            'sales_total' => (float) (clone $saleQuery)->sum('total'),
            'payments_total' => (float) $paymentTotal,
            'purchases_total' => (float) (clone $purchaseQuery)->sum('total'),
            'expenses_total' => (float) (clone $expenseQuery)->sum('amount'),
            'inventory_items' => Inventory::query()->where('branch_id', $branch->id)->count(),
            'inventory_quantity' => Inventory::query()->where('branch_id', $branch->id)->sum('quantity'),
            'assigned_staff' => $branch->staff()->count(),
            'available_services' => $branch->services()->wherePivot('is_active', true)->count(),
        ];

        $recentAppointments = (clone $appointmentQuery)
            ->with(['customer', 'staff'])
            ->latest('starts_at')
            ->limit(8)
            ->get();

        $recentSales = (clone $saleQuery)
            ->with('customer')
            ->latest()
            ->limit(8)
            ->get();

        if ($request->filled('format')) {
            $currency = $branch->tenant?->currency ?: 'LKR';
            $document = new ReportDocument('Branch Report: ' . $branch->name, $branch->tenant?->name ?? '', [
                'Period' => $startDate->format('M d, Y') . ' – ' . $endDate->format('M d, Y'),
            ], [
                'Appointments' => number_format($metrics['appointments']),
                'Sales' => number_format($metrics['sales']) . ' · ' . $currency . ' ' . number_format($metrics['sales_total'], 2),
                'Net collections' => $currency . ' ' . number_format($metrics['payments_total'], 2),
                'Purchases' => $currency . ' ' . number_format($metrics['purchases_total'], 2),
                'Expenses' => $currency . ' ' . number_format($metrics['expenses_total'], 2),
                'Staff assigned' => (string) $metrics['assigned_staff'],
                'Services offered' => (string) $metrics['available_services'],
                'Stock on hand' => number_format($metrics['inventory_quantity']) . ' units',
            ], $currency);

            $document->table(new ReportTable('Recent Appointments', ['Date', 'Customer', 'Staff', 'Status'],
                $recentAppointments->map(fn ($appointment) => [$appointment->starts_at?->format('Y-m-d H:i'), $appointment->customer?->full_name, $appointment->staff?->full_name, $appointment->status_label])));
            $document->table(new ReportTable('Recent Sales', ['Invoice', 'Date', 'Customer', 'Total'],
                $recentSales->map(fn ($sale) => [$sale->invoice_number, $sale->created_at?->format('Y-m-d H:i'), $sale->customer?->full_name, $sale->total]), [3]));

            return app(ReportExporter::class)->download($document, $request->string('format')->toString());
        }

        return view('pages/apps.branch-management.branches.report', [
            'branch' => $branch,
            'metrics' => $metrics,
            'recentAppointments' => $recentAppointments,
            'recentSales' => $recentSales,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
}
