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
use App\Services\Billing\CheckoutService;
use App\Services\Billing\PaymentMethodService;
use App\Services\Billing\PosPinService;
use App\Services\Billing\TaxService;
use App\Services\BranchContext;
use App\Services\CustomerManagementService;
use App\Services\Loyalty\LoyaltyService;
use App\Services\PlanEntitlementService;
use App\Services\Settings\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        PosPinService $posPin
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
            'products' => Product::withoutTenantScope()
                ->with(['inventories' => fn ($query) => $query->where('branch_id', $branch->id)])
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->where('is_sellable', true)
                ->orderBy('name')
                ->get(),
            'customers' => $customers,
            'loyaltyPoints' => $loyaltyPoints,
            'paymentMethods' => PaymentMethod::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->active()
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
        ]);
    }

    public function store(
        QuickSaleRequest $request,
        BranchContext $branchContext,
        CheckoutService $checkout,
        CustomerManagementService $customers,
        PlanEntitlementService $entitlements
    ): RedirectResponse {
        $user = $request->user();
        $tenant = $user->tenant;
        $entitlements->ensureFeature($tenant, 'pos_billing');
        $branch = $branchContext->currentForOperation($user);
        $data = $request->validated();
        $data['cashier_staff_id'] = $request->cashierStaffId();

        if ($data['customer_mode'] === QuickSaleRequest::CUSTOMER_NEW) {
            if (! $entitlements->featureEnabled($tenant, 'customer_management')) {
                throw ValidationException::withMessages([
                    'customer_mode' => 'Your subscription plan does not include customer management.',
                ]);
            }

            $entitlements->ensureCanCreate($tenant, 'max_customers', 'Your subscription customer limit has been reached.');
        }

        // The new customer and the sale commit together, so a failed payment leaves no orphan customer.
        $invoice = DB::transaction(function () use ($data, $tenant, $branch, $user, $checkout, $customers) {
            $customer = match ($data['customer_mode']) {
                QuickSaleRequest::CUSTOMER_EXISTING => Customer::withoutTenantScope()->where('tenant_id', $tenant->id)->findOrFail($data['customer_id']),
                QuickSaleRequest::CUSTOMER_NEW => $customers->create($tenant, [
                    'branch_id' => $branch->id,
                    'first_name' => $data['new_customer']['first_name'],
                    'last_name' => $data['new_customer']['last_name'] ?? null,
                    'phone' => $data['new_customer']['phone'],
                    'email' => $data['new_customer']['email'] ?? null,
                    'status' => Customer::STATUS_ACTIVE,
                ], $user),
                default => null,
            };

            return $checkout->quickSale($branch, $customer, $data, $user);
        });

        return redirect()
            ->route('billing.invoices.show', $invoice)
            ->with('status', 'Sale completed and invoice ' . $invoice->invoice_number . ' created.')
            ->with('print_receipt', true);
    }
}
