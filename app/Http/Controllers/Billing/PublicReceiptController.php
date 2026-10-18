<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Settings\SettingService;
use Illuminate\View\View;

/**
 * The receipt link sent to customers by SMS/WhatsApp. It needs no login; the signed, expiring URL
 * (checked by the "signed" middleware) is what grants access.
 */
class PublicReceiptController extends Controller
{
    public function show(int $invoice, SettingService $settings): View
    {
        $invoice = Invoice::withoutTenantScope()
            ->withoutGlobalScopes()
            ->with(['tenant', 'branch', 'customer', 'appointment', 'cashier', 'items.staff', 'payments.paymentMethod', 'payments.giftCard'])
            ->findOrFail($invoice);

        $setting = fn (string $key) => $settings->get($key, $invoice->tenant_id, $invoice->branch_id);

        return view('pages/apps.billing.invoices.receipt-thermal', [
            'invoice' => $invoice,
            'autoPrint' => false,
            'publicView' => true,
            'showLogo' => (bool) $setting('invoice.show_logo') && filled($invoice->tenant?->logo_path),
            'showCustomer' => (bool) $setting('invoice.show_customer_details'),
            'footerMessage' => $setting('invoice.footer_message'),
            'terms' => $setting('invoice.terms'),
        ]);
    }
}
