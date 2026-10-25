<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\QuickSaleRequest;
use App\Models\Customer;
use App\Models\CustomerLoyaltyAccount;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Service;
use App\Models\Staff;
use App\Services\Billing\CashRegisterService;
use App\Services\Billing\PackageService;
use App\Services\Billing\PaymentMethodService;
use App\Services\Billing\PosPinService;
use App\Services\Billing\QuickSaleService;
use App\Services\Billing\TaxService;
use App\Services\BranchContext;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Messaging\CustomerNotifier;
use App\Services\PlanEntitlementService;
use App\Services\Settings\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuickSaleController extends Controller
{
    public function create(
        Request $request,
        BranchContext $branchContext,
        PaymentMethodService $paymentMethods,
        PlanEntitlementService $entitlements,
        TaxService $taxes,
        SettingService $settings,
        LoyaltyService $loyalty,
        CashRegisterService $cashRegister,
        PosPinService $posPin,
        PackageService $packages
    ): View {
        $this->authorize('create', Invoice::class);

        $user = $request->user();
        abort_unless($user->tenant_id, 403, 'Quick sale is available to salon users only.');

        $tenant = $user->tenant;
        $entitlements->ensureFeature($tenant, 'pos_billing');
        $branch = $branchContext->currentForOperation($user);
        $paymentMethods->ensureDefaults($tenant);

        $staff = Staff::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('status', Staff::STATUS_ACTIVE)
            ->whereHas('branches', fn ($query) => $query->where('branches.id', $branch->id)->where('staff_branches.status', 'active'))
            ->with(['services' => fn ($query) => $query->wherePivot('status', 'active')])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $customers = Customer::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('status', Customer::STATUS_ACTIVE)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $loyaltyPoints = $loyalty->activeProgram($tenant)
            ? CustomerLoyaltyAccount::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->where('status', CustomerLoyaltyAccount::STATUS_ACTIVE)
                ->whereIn('customer_id', $customers->pluck('id'))
                ->pluck('available_points', 'customer_id')
            : collect();

        return view('pages/apps.billing.quick-sale.create', [
            'branch' => $branch,
            'services' => Service::withoutTenantScope()
                ->with(['branches', 'category'])
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->availableAtBranch($branch)
                ->orderBy('name')
                ->get(),
            'staff' => $staff,
            'products' => Product::applyBranchPrices(Product::withoutTenantScope()
                ->with(['inventories' => fn ($query) => $query->where('branch_id', $branch->id)])
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->where('is_sellable', true)
                ->orderBy('name')
                ->get(), $branch->id),
            'customers' => $customers,
            'loyaltyPoints' => $loyaltyPoints,
            'paymentMethods' => PaymentMethod::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->tenderable()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'promotions' => Promotion::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->whereIn('application_type', [Promotion::APPLICATION_AUTOMATIC, Promotion::APPLICATION_MANUAL])
                ->active()
                ->orderByDesc('priority')
                ->orderBy('name')
                ->get(),
            'taxConfig' => $taxes->configFor($branch),
            'canApplyDiscount' => $user->can('applyDiscount', Invoice::class)
                && $settings->get('pos.allow_manual_discount', $tenant->id, $branch->id),
            'allowWalkIns' => (bool) $settings->get('booking.allow_walk_ins', $tenant->id, $branch->id),
            'canCreateCustomer' => $user->can('create', Customer::class)
                && $entitlements->featureEnabled($tenant, 'customer_management'),
            'allowSplitPayment' => (bool) $settings->get('pos.allow_split_payment', $tenant->id, $branch->id),
            'cashShift' => $cashRegister->openShiftFor($branch->id),
            'requireShift' => $cashRegister->requiresOpenShift($tenant->id, $branch->id),
            'posPin' => $posPin->viewData($branch, $user),
            'tipStaff' => $staff,
            'packageCredits' => $packages->creditsByCustomer($tenant->id, $customers->pluck('id')),
        ]);
    }

    public function store(QuickSaleRequest $request, BranchContext $branchContext, QuickSaleService $quickSales, PlanEntitlementService $entitlements): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $entitlements->ensureFeature($user->tenant, 'pos_billing');
        $branch = $branchContext->currentForOperation($user);
        $data = $request->validated();
        $data['cashier_staff_id'] = $request->cashierStaffId();

        $alreadySaved = $quickSales->existing($user->tenant_id, $data['client_uuid'] ?? null) !== null;
        $invoice = $quickSales->create($branch, $data, $user);

        if (! $alreadySaved) {
            app(CustomerNotifier::class)->invoiceReceipt($invoice, $user);
        }

        return self::saleResponse($request, $invoice, $alreadySaved);
    }

    /**
     * A fresh CSRF token for the offline sync, which may run long after the page was loaded.
     */
    public function csrfToken(): JsonResponse
    {
        return response()->json(['token' => csrf_token()]);
    }

    /**
     * The offline sync posts with Accept: application/json and gets the invoice back; the normal form
     * is redirected to the invoice.
     */
    public static function saleResponse(Request $request, Invoice $invoice, bool $alreadySaved = false): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'total' => (float) $invoice->total,
                'balance' => (float) $invoice->balance_amount,
                'url' => route('billing.invoices.show', $invoice),
                'duplicate' => $alreadySaved,
            ], $alreadySaved ? 200 : 201);
        }

        return redirect()
            ->route('billing.invoices.show', $invoice)
            ->with('status', $alreadySaved
                ? 'This sale was already saved as invoice ' . $invoice->invoice_number . '.'
                : 'Sale completed and invoice ' . $invoice->invoice_number . ' created.')
            ->with('print_receipt', ! $alreadySaved);
    }
}
