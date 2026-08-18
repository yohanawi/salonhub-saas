<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\PayPayrollRunRequest;
use App\Models\PayrollRun;
use App\Services\Payroll\PayrollPaymentService;
use App\Services\PlanEntitlementService;
use Illuminate\Http\RedirectResponse;

class PayrollPaymentController extends Controller
{
    public function store(PayPayrollRunRequest $request, PayrollRun $payrollRun, PayrollPaymentService $payments, PlanEntitlementService $entitlements): RedirectResponse
    {
        $entitlements->ensureFeature($payrollRun->tenant, 'payroll');
        $payments->payRun($payrollRun, $request->validated(), $request->user());

        return back()->with('status', 'Payroll paid successfully.');
    }
}
