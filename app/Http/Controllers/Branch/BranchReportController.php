<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Inventory;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchReportController extends Controller
{
    public function show(Request $request, Branch $branch): View
    {
        $this->authorize('viewReports', $branch);

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

        $paymentTotal = Payment::query()
            ->whereHas('sale', fn ($query) => $query->where('branch_id', $branch->id))
            ->whereBetween('paid_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
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
