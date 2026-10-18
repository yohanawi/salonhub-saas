<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Refund;
use App\Models\Tenant;
use App\Services\Billing\CashRegisterService;
use App\Services\Billing\DepositService;
use App\Services\Billing\GiftCardService;
use App\Services\Billing\PackageService;
use App\Services\Billing\TipService;
use App\Services\Messaging\CustomerNotifier;
use App\Services\Messaging\MessagingService;
use App\Services\Billing\PosPinService;
use App\Services\Billing\RefundService;
use App\Services\BranchContext;
use App\Services\Commission\CommissionGenerator;
use App\Services\Inventory\InventoryService;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Membership\MembershipBenefitService;
use App\Services\PlanEntitlementService;
use App\Services\Promotions\PromotionService;
use App\Services\Settings\SettingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request, BranchContext $branchContext, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', Invoice::class);

        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : ($isSuperAdmin ? null : $request->user()->tenant);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'pos_billing');
        }

        $visibleBranches = $isSuperAdmin
            ? Branch::withoutTenantScope()->orderBy('name')->get()
            : $branchContext->availableBranches($request->user());

        $invoices = ($isSuperAdmin ? Invoice::withoutTenantScope() : Invoice::query()->where('tenant_id', $tenant->id))
            ->with(['tenant', 'branch', 'customer', 'appointment', 'cashier', 'items.staff', 'payments.paymentMethod', 'payments.giftCard'])
            ->when(! $isSuperAdmin && ! ($branchContext->hasTenantWideBranchAccess($request->user()) || $request->user()->can('billing.view_all_branches')), function (Builder $query) use ($visibleBranches) {
                $query->whereIn('branch_id', $visibleBranches->pluck('id'));
            })
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn(Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when($request->filled('branch_id'), fn(Builder $query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('status'), fn(Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('payment_status'), fn(Builder $query) => $query->where('payment_status', $request->string('payment_status')->toString()))
            ->when($request->filled('date'), fn(Builder $query) => $query->whereDate('issued_at', $request->date('date')))
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function (Builder $query) use ($search) {
                    $query->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn(Builder $query) => $query->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                        ->orWhereHas('appointment', fn(Builder $query) => $query->where('appointment_number', 'like', "%{$search}%"));
                });
            })
            ->latest('issued_at')
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.billing.invoices.index', [
            'invoices' => $invoices,
            'branches' => $visibleBranches,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
            'statuses' => Invoice::STATUSES,
            'paymentStatuses' => Invoice::PAYMENT_STATUSES,
        ]);
    }

    public function show(Request $request, Invoice $invoice, PlanEntitlementService $entitlements, RefundService $refunds, SettingService $settings, CashRegisterService $cashRegister, PosPinService $posPin): View
    {
        $this->authorize('view', $invoice);

        if (! $request->user()->hasRole('Super Admin')) {
            $entitlements->ensureFeature($invoice->tenant, 'pos_billing');
        }

        $invoice->load(['tenant', 'branch', 'customer', 'appointment', 'cashier', 'payments.cashier', 'payments.giftCard', 'tips.staff', 'items.staff', 'promotion', 'promotionCoupon', 'promotionUsages', 'payments.paymentMethod', 'payments.receiver', 'createdBy', 'voidedBy', 'refunds.items.invoiceItem', 'refunds.paymentMethod', 'refunds.requestedBy', 'refunds.processedBy']);

        $refundableAmount = $refunds->refundableAmount($invoice);
        $canRequestRefund = $request->user()->can('refund', $invoice)
            && $invoice->status === Invoice::STATUS_ISSUED
            && in_array($invoice->payment_status, [Invoice::PAYMENT_PAID, Invoice::PAYMENT_PARTIALLY_REFUNDED], true)
            && $refundableAmount > 0
            && $settings->get('pos.allow_refunds', $invoice->tenant_id, $invoice->branch_id);
        $methods = fn (string $scope) => PaymentMethod::withoutTenantScope()
            ->where('tenant_id', $invoice->tenant_id)
            ->{$scope}()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('pages/apps.billing.invoices.show', [
            'invoice' => $invoice,
            'paymentMethods' => $request->user()->can('collectPayment', $invoice) ? $methods('tenderable') : collect(),
            'canRequestRefund' => $canRequestRefund,
            'canApproveRefund' => $request->user()->can('approveRefund', $invoice),
            'refundMethods' => $canRequestRefund ? $methods('manual') : collect(),
            'returnableItems' => $canRequestRefund ? $refunds->returnableItems($invoice) : collect(),
            'refundableAmount' => $refundableAmount,
            'allowSplitPayment' => (bool) $settings->get('pos.allow_split_payment', $invoice->tenant_id, $invoice->branch_id),
            'cashShift' => $cashRegister->openShiftFor($invoice->branch_id),
            'requireShift' => $cashRegister->requiresOpenShift($invoice->tenant_id, $invoice->branch_id),
            'receiptFormat' => $settings->get('invoice.receipt_format', $invoice->tenant_id, $invoice->branch_id),
            'canSendReceipt' => app(MessagingService::class)->enabled($invoice->tenant_id, $invoice->branch_id) && $request->user()->can('create', Invoice::class),
            // Only straight after the sale, so reopening the invoice later does not print again.
            'autoPrintReceipt' => (bool) $request->session()->get('print_receipt')
                && $settings->get('pos.auto_print_receipt', $invoice->tenant_id, $invoice->branch_id),
            'posPin' => $request->user()->can('collectPayment', $invoice) && $invoice->branch
                ? $posPin->viewData($invoice->branch, $request->user())
                : null,
        ]);
    }

    /**
     * Standalone 80mm receipt for counter thermal printers. `?print=1` opens the print dialog on load.
     */
    public function receipt(Request $request, Invoice $invoice, PlanEntitlementService $entitlements, SettingService $settings): View
    {
        $this->authorize('view', $invoice);

        if (! $request->user()->hasRole('Super Admin')) {
            $entitlements->ensureFeature($invoice->tenant, 'pos_billing');
        }

        $invoice->load(['tenant', 'branch', 'customer', 'appointment', 'cashier', 'items.staff', 'payments.paymentMethod', 'payments.giftCard']);
        $setting = fn (string $key) => $settings->get($key, $invoice->tenant_id, $invoice->branch_id);

        return view('pages/apps.billing.invoices.receipt-thermal', [
            'invoice' => $invoice,
            'autoPrint' => $request->boolean('print'),
            'showLogo' => (bool) $setting('invoice.show_logo') && filled($invoice->tenant?->logo_path),
            'showCustomer' => (bool) $setting('invoice.show_customer_details'),
            'footerMessage' => $setting('invoice.footer_message'),
            'terms' => $setting('invoice.terms'),
        ]);
    }

    public function sendReceipt(Request $request, Invoice $invoice, CustomerNotifier $notifier, MessagingService $messaging): RedirectResponse
    {
        $this->authorize('view', $invoice);
        abort_unless($request->user()->can('create', Invoice::class), 403);

        $data = $request->validate(['phone' => ['nullable', 'string', 'max:30']]);

        if (! $messaging->enabled($invoice->tenant_id, $invoice->branch_id)) {
            throw ValidationException::withMessages(['phone' => 'SMS / WhatsApp is not set up. Add the provider in Settings first.']);
        }

        $log = $notifier->invoiceReceipt($invoice, $request->user(), $data['phone'] ?? null, manual: true);

        if (! $log) {
            throw ValidationException::withMessages(['phone' => 'Enter a valid mobile number for the customer.']);
        }

        $log->refresh();

        return back()->with('status', $log->status === \App\Models\MessageLog::STATUS_FAILED
            ? 'The receipt could not be sent: ' . $log->error
            : 'Receipt sent to ' . $log->recipient . '.');
    }

    public function void(Request $request, Invoice $invoice, InventoryService $inventory, CommissionGenerator $commissions, LoyaltyService $loyalty, PromotionService $promotions, MembershipBenefitService $membershipBenefits, GiftCardService $giftCards, PackageService $packages, DepositService $deposits, TipService $tips): RedirectResponse
    {
        $this->authorize('void', $invoice);

        $data = $request->validate([
            'void_reason' => ['required', 'string', 'max:3000'],
        ]);

        // All reversals succeed or none do, so a failure cannot leave a void invoice with live stock/commission/loyalty records.
        DB::transaction(function () use ($request, $invoice, $data, $inventory, $commissions, $loyalty, $promotions, $membershipBenefits, $giftCards, $packages, $deposits, $tips) {
            $locked = Invoice::withoutTenantScope()->whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === Invoice::STATUS_VOID) {
                throw ValidationException::withMessages(['invoice' => 'This invoice has already been voided.']);
            }

            // Voiding reverses the whole sale again, so it would double-count stock and commission already returned.
            if ($locked->refunds()->whereIn('status', [Refund::STATUS_PENDING, Refund::STATUS_COMPLETED])->exists()) {
                throw ValidationException::withMessages(['invoice' => 'This invoice has refunds. Use a refund instead of voiding it.']);
            }

            // A gift card or package sold on this invoice can only be cancelled while it is unused.
            $giftCards->voidSoldOnInvoice($invoice, $request->user(), $data['void_reason']);
            $packages->cancelSoldOnInvoice($invoice);

            $invoice->update([
                'status' => Invoice::STATUS_VOID,
                'void_reason' => $data['void_reason'],
                'voided_by' => $request->user()->id,
                'voided_at' => now(),
                'updated_by' => $request->user()->id,
            ]);

            $invoice->payments()->where('status', Payment::STATUS_COMPLETED)->update(['status' => Payment::STATUS_VOID]);

            $inventory->returnInvoiceProducts($invoice, $request->user(), $data['void_reason']);
            $commissions->reverseForInvoice($invoice, $request->user(), $data['void_reason']);
            $loyalty->reverseForInvoice($invoice, $request->user(), $data['void_reason']);
            $promotions->reverseForInvoice($invoice, $request->user(), $data['void_reason']);
            $membershipBenefits->reverseInvoiceUsage($invoice);
            $giftCards->reverseForInvoice($invoice, $request->user());
            $deposits->reverseForInvoice($invoice);
            $packages->reverseUsageForInvoice($invoice);
            $tips->voidForInvoice($invoice);
        });

        return back()->with('status', 'Invoice voided successfully.');
    }
}
