<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Services\Expense\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExpenseApprovalController extends Controller
{
    public function approve(Request $request, Expense $expense, ExpenseService $service): RedirectResponse
    {
        $this->authorize('approve', $expense);

        $service->approve($expense, $request->user());

        return back()->with('status', 'Expense approved successfully.');
    }

    public function reject(Request $request, Expense $expense, ExpenseService $service): RedirectResponse
    {
        $this->authorize('reject', $expense);

        $service->reject($expense, $request->user());

        return back()->with('status', 'Expense rejected successfully.');
    }

    public function cancel(Request $request, Expense $expense, ExpenseService $service): RedirectResponse
    {
        $this->authorize('cancel', $expense);

        $data = $request->validate([
            'cancel_reason' => ['required', 'string', 'max:3000'],
        ]);

        $service->cancel($expense, $data['cancel_reason'], $request->user());

        return back()->with('status', 'Expense cancelled successfully.');
    }
}
