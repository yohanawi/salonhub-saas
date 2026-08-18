<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpensePaymentRequest;
use App\Models\Expense;
use App\Services\Expense\ExpensePaymentService;
use Illuminate\Http\RedirectResponse;

class ExpensePaymentController extends Controller
{
    public function store(StoreExpensePaymentRequest $request, Expense $expense, ExpensePaymentService $payments): RedirectResponse
    {
        $payments->record($expense, $request->validated(), $request->user());

        return back()->with('status', 'Expense payment recorded successfully.');
    }
}
