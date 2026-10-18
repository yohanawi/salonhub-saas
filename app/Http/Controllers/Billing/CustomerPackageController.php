<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\SellPackageRequest;
use App\Models\Customer;
use App\Models\CustomerPackage;
use App\Models\PaymentMethod;
use App\Models\ServicePackage;
use App\Models\Tenant;
use App\Services\Billing\CashRegisterService;
use App\Services\Billing\PackageService;
use App\Services\Billing\PaymentMethodService;
use App\Services\Billing\PosPinService;
use App\Services\Billing\TaxService;
use App\Services\BranchContext;
use App\Services\PlanEntitlementService;
use App\Services\Settings\SettingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerPackageController extends Controller
{
    public function index(Request $request, PlanEntitlementService $entitlements): View
    {
        $tenant = $this->tenant($request, 'service_packages.view', $entitlements);

        $packages = CustomerPackage::withoutTenantScope()
            ->with(['customer', 'items'])
            ->where('tenant_id', $tenant->id)
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->string('search')->trim()->toString();

                $query->where(function (Builder $query) use ($search) {
                    $query->where('package_number', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn (Builder $query) => $query->withoutGlobalScopes()->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('pages/apps.billing.customer-packages.index', [
            'packages' => $packages,
            'canSell' => $request->user()->can('service_packages.sell'),
        ]);
    }

    public function create(
        Request $request,
        PlanEntitlementService $entitlements,
        BranchContext $branchContext,
        PaymentMethodService $paymentMethods,
        SettingService $settings,
        CashRegisterService $cashRegister,
        PosPinService $posPin,
        TaxService $taxes
    ): View {
        $tenant = $this->tenant($request, 'service_packages.sell', $entitlements);
        $branch = $branchContext->currentForOperation($request->user());
        $paymentMethods->ensureDefaults($tenant);

        return view('pages/apps.billing.customer-packages.create', [
            'branch' => $branch,
            'packages' => ServicePackage::withoutTenantScope()->with('items.service')->where('tenant_id', $tenant->id)->active()->orderBy('name')->get(),
            'customers' => Customer::withoutTenantScope()->where('tenant_id', $tenant->id)->where('status', Customer::STATUS_ACTIVE)->orderBy('first_name')->get(),
            'paymentMethods' => PaymentMethod::withoutTenantScope()->where('tenant_id', $tenant->id)->tenderable()->orderBy('sort_order')->orderBy('name')->get(),
            'allowSplitPayment' => (bool) $settings->get('pos.allow_split_payment', $tenant->id, $branch->id),
            'cashShift' => $cashRegister->openShiftFor($branch->id),
            'requireShift' => $cashRegister->requiresOpenShift($tenant->id, $branch->id),
            'posPin' => $posPin->viewData($branch, $request->user()),
            'taxConfig' => $taxes->configFor($branch),
        ]);
    }

    public function store(SellPackageRequest $request, BranchContext $branchContext, PackageService $packages, PlanEntitlementService $entitlements): RedirectResponse
    {
        $tenant = $request->user()->tenant;
        $entitlements->ensureFeature($tenant, 'pos_billing');
        $branch = $branchContext->currentForOperation($request->user());
        $data = $request->validated();

        $customerPackage = $packages->sell(
            $branch,
            Customer::withoutTenantScope()->where('tenant_id', $tenant->id)->findOrFail($data['customer_id']),
            ServicePackage::withoutTenantScope()->where('tenant_id', $tenant->id)->findOrFail($data['service_package_id']),
            $data,
            $request->user(),
            $request->cashierStaffId()
        );

        return redirect()
            ->route('billing.customer-packages.show', $customerPackage)
            ->with('status', 'Package ' . $customerPackage->package_number . ' sold to ' . $customerPackage->customer->full_name . '.');
    }

    public function show(Request $request, CustomerPackage $customerPackage, PlanEntitlementService $entitlements): View
    {
        $tenant = $this->tenant($request, 'service_packages.view', $entitlements);
        abort_unless((int) $customerPackage->tenant_id === (int) $tenant->id, 404);

        $customerPackage->load(['customer', 'invoice', 'items.usages.invoice']);

        return view('pages/apps.billing.customer-packages.show', [
            'customerPackage' => $customerPackage,
        ]);
    }

    private function tenant(Request $request, string $permission, PlanEntitlementService $entitlements): Tenant
    {
        $user = $request->user();
        abort_unless($user->tenant_id && $user->can($permission), 403);
        $entitlements->ensureFeature($user->tenant, 'pos_billing');

        return $user->tenant;
    }
}
