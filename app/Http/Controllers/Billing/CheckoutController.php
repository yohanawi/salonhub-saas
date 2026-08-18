<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\CheckoutRequest;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Promotion;
use App\Models\Product;
use App\Services\Billing\CheckoutService;
use App\Services\Billing\PaymentMethodService;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Membership\MembershipBenefitService;
use App\Services\PlanEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function create(Request $request, Appointment $appointment, PaymentMethodService $paymentMethods, PlanEntitlementService $entitlements, LoyaltyService $loyalty, MembershipBenefitService $membershipBenefits): View|RedirectResponse
    {
        $this->authorize('checkout', Invoice::class);
        $this->authorize('view', $appointment);

        $entitlements->ensureFeature($appointment->tenant, 'pos_billing');

        if ($appointment->status !== Appointment::STATUS_COMPLETED) {
            return redirect()
                ->route('appointment-management.appointments.show', $appointment)
                ->withErrors(['appointment' => 'Only completed appointments can be checked out.']);
        }

        $existingInvoice = Invoice::withoutTenantScope()
            ->where('tenant_id', $appointment->tenant_id)
            ->where('appointment_id', $appointment->id)
            ->first();

        if ($existingInvoice) {
            return redirect()->route('billing.invoices.show', $existingInvoice);
        }

        $paymentMethods->ensureDefaults($appointment->tenant);

        $appointment->load(['branch', 'customer', 'appointmentServices.service', 'appointmentServices.staff']);

        return view('pages/apps.billing.checkout.create', [
            'appointment' => $appointment,
            'paymentMethods' => PaymentMethod::withoutTenantScope()
                ->where('tenant_id', $appointment->tenant_id)
                ->active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'products' => Product::withoutTenantScope()
                ->with(['unit', 'inventories' => fn ($query) => $query->where('branch_id', $appointment->branch_id)])
                ->where('tenant_id', $appointment->tenant_id)
                ->where('is_active', true)
                ->where('is_sellable', true)
                ->orderBy('name')
                ->get(),
            'loyaltyAccount' => $appointment->customer ? $loyalty->accountFor($appointment->customer) : null,
            'activeMembership' => $appointment->customer ? $membershipBenefits->activeMembership($appointment->customer) : null,
            'promotions' => Promotion::withoutTenantScope()
                ->where('tenant_id', $appointment->tenant_id)
                ->whereIn('application_type', [Promotion::APPLICATION_AUTOMATIC, Promotion::APPLICATION_MANUAL])
                ->active()
                ->orderByDesc('priority')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(CheckoutRequest $request, Appointment $appointment, CheckoutService $checkout, PlanEntitlementService $entitlements): RedirectResponse
    {
        $this->authorize('view', $appointment);
        $entitlements->ensureFeature($appointment->tenant, 'pos_billing');

        $invoice = $checkout->checkoutAppointment($appointment, $request->validated(), $request->user());

        return redirect()
            ->route('billing.invoices.show', $invoice)
            ->with('status', 'Checkout completed and invoice created.');
    }
}
