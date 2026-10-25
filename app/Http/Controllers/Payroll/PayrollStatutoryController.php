<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Services\Payroll\PayrollExportService;
use App\Services\PlanEntitlementService;
use App\Services\Settings\SettingService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Monthly EPF (employee + employer) and ETF contribution schedule for remittance.
 */
class PayrollStatutoryController extends Controller
{
    public function index(Request $request, PayrollExportService $exports, PlanEntitlementService $entitlements, SettingService $settings): View
    {
        [$tenant, $periods, $period] = $this->context($request, $entitlements);
        $rows = $period ? $exports->statutoryRows($period) : collect();

        return view('pages/apps.payroll.statutory.index', [
            'periods' => $periods,
            'period' => $period,
            'rows' => $rows,
            'totals' => [
                'earnings' => $rows->sum('earnings'),
                'epf_employee' => $rows->sum('epf_employee'),
                'epf_employer' => $rows->sum('epf_employer'),
                'epf_total' => $rows->sum('epf_total'),
                'etf' => $rows->sum('etf'),
            ],
            'employerEpfNumber' => $settings->get('payroll.employer_epf_number', $tenant->id),
            'epfEnabled' => (bool) $settings->get('payroll.epf_enabled', $tenant->id),
            'currency' => $tenant->currency ?: 'LKR',
        ]);
    }

    public function export(Request $request, PayrollExportService $exports, PlanEntitlementService $entitlements): StreamedResponse
    {
        [, , $period] = $this->context($request, $entitlements);
        abort_unless($period, 404);

        $rows = $exports->statutoryRows($period)->map(fn (array $row) => [
            $row['employee_code'],
            $row['employee_name'],
            $row['epf_number'],
            number_format($row['earnings'], 2, '.', ''),
            number_format($row['epf_employee'], 2, '.', ''),
            number_format($row['epf_employer'], 2, '.', ''),
            number_format($row['epf_total'], 2, '.', ''),
            number_format($row['etf'], 2, '.', ''),
        ]);

        return $exports->csv(
            'epf-etf-' . str($period->name)->slug() . '.csv',
            ['Employee Code', 'Employee Name', 'EPF Member No', 'Total Earnings', 'EPF Employee (8%)', 'EPF Employer (12%)', 'EPF Total', 'ETF (3%)'],
            $rows
        );
    }

    private function context(Request $request, PlanEntitlementService $entitlements): array
    {
        $user = $request->user();
        abort_unless($user->tenant_id && ($user->can('payroll.report.view') || $user->can('payroll.view')), 403);

        $tenant = $user->tenant;
        $entitlements->ensureFeature($tenant, 'payroll');

        $periods = PayrollPeriod::withoutTenantScope()->where('tenant_id', $tenant->id)->latest('end_date')->get();
        $period = $request->filled('period_id')
            ? $periods->firstWhere('id', $request->integer('period_id'))
            : $periods->first();

        return [$tenant, $periods, $period];
    }
}
