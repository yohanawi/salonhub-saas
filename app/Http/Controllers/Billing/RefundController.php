<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\StoreRefundRequest;
use App\Models\Invoice;
use App\Models\Refund;
use App\Services\Billing\RefundService;
use App\Services\PlanEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function store(StoreRefundRequest $request, Invoice $invoice, RefundService $refunds, PlanEntitlementService $entitlements): RedirectResponse
    {
        $this->ensureBilling($request, $invoice, $entitlements);

        $refund = $refunds->request($invoice, $request->validated(), $request->user());

        return redirect()
            ->route('billing.invoices.show', $invoice)
            ->with('status', $refund->status === Refund::STATUS_COMPLETED
                ? "Refund {$refund->refund_number} of LKR " . number_format((float) $refund->amount, 2) . ' completed.'
                : "Refund {$refund->refund_number} submitted for approval.");
    }

    public function approve(Request $request, Invoice $invoice, Refund $refund, RefundService $refunds, PlanEntitlementService $entitlements): RedirectResponse
    {
        $this->authorize('approveRefund', $invoice);
        abort_unless((int) $refund->sale_id === (int) $invoice->id, 404);
        $this->ensureBilling($request, $invoice, $entitlements);

        $refund = $refunds->approve($refund, $request->user());

        return back()->with('status', "Refund {$refund->refund_number} approved and completed.");
    }

    public function reject(Request $request, Invoice $invoice, Refund $refund, RefundService $refunds, PlanEntitlementService $entitlements): RedirectResponse
    {
        $this->authorize('approveRefund', $invoice);
        abort_unless((int) $refund->sale_id === (int) $invoice->id, 404);
        $this->ensureBilling($request, $invoice, $entitlements);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:3000'],
        ]);

        $refund = $refunds->reject($refund, $request->user(), $data['rejection_reason']);

        return back()->with('status', "Refund {$refund->refund_number} rejected.");
    }

    private function ensureBilling(Request $request, Invoice $invoice, PlanEntitlementService $entitlements): void
    {
        if (! $request->user()->hasRole('Super Admin')) {
            $entitlements->ensureFeature($invoice->tenant, 'pos_billing');
        }
    }
}
