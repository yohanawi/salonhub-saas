<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PayrollItem;
use App\Services\Payroll\PayslipService;
use App\Services\PlanEntitlementService;
use Illuminate\View\View;

class PayslipController extends Controller
{
    public function show(PayrollItem $payrollItem, PayslipService $payslips, PlanEntitlementService $entitlements): View
    {
        $this->authorize('view', $payrollItem);
        $entitlements->ensureFeature($payrollItem->tenant, 'payroll');

        return view('pages/apps.payroll.payslips.show', $payslips->data($payrollItem));
    }
}
