<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PayrollItem;
use App\Services\Payroll\PayslipService;
use App\Services\PlanEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PayslipController extends Controller
{
    public function show(PayrollItem $payrollItem, PayslipService $payslips, PlanEntitlementService $entitlements): View
    {
        $this->authorize('view', $payrollItem);
        $entitlements->ensureFeature($payrollItem->tenant, 'payroll');

        return view('pages/apps.payroll.payslips.show', $payslips->data($payrollItem));
    }

    public function pdf(PayrollItem $payrollItem, PayslipService $payslips, PlanEntitlementService $entitlements): Response
    {
        $this->authorize('view', $payrollItem);
        $entitlements->ensureFeature($payrollItem->tenant, 'payroll');

        return $this->pdfResponse($payrollItem, $payslips);
    }

    public function send(Request $request, PayrollItem $payrollItem, PayslipService $payslips, PlanEntitlementService $entitlements): RedirectResponse
    {
        $this->authorize('approve', $payrollItem->run);
        $entitlements->ensureFeature($payrollItem->tenant, 'payroll');

        $data = $request->validate([
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => [Rule::in(['email', 'message'])],
        ]);

        $sent = $payslips->send($payrollItem, $data['channels'], $request->user());

        return back()->with(
            $sent ? 'status' : 'error',
            $sent
                ? 'Payslip sent by ' . implode(' and ', array_map(fn ($channel) => $channel === 'email' ? 'email' : 'SMS/WhatsApp', $sent)) . '.'
                : 'Nothing was sent: add an email or phone number to the staff profile, and set up SMS/WhatsApp in Settings for messages.'
        );
    }

    /**
     * The no-login link sent by SMS/WhatsApp. The "signed" middleware has already verified the URL.
     */
    public function publicPdf(int $payslip, PayslipService $payslips): Response
    {
        $item = PayrollItem::withoutTenantScope()->findOrFail($payslip);

        abort_unless(in_array($item->status, [PayrollItem::STATUS_APPROVED, PayrollItem::STATUS_PAID], true), 404);

        return $this->pdfResponse($item, $payslips, inline: true);
    }

    private function pdfResponse(PayrollItem $item, PayslipService $payslips, bool $inline = false): Response
    {
        return response($payslips->pdf($item), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment') . '; filename="' . $payslips->filename($item) . '"',
        ]);
    }
}
