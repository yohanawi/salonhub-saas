<?php

namespace App\Http\Controllers\CustomerManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerManagement\StoreCustomerRequest;
use App\Http\Requests\CustomerManagement\UpdateCustomerRequest;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Tenant;
use App\Services\CustomerManagementService;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', Customer::class);

        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin ? null : $this->tenant($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'customer_management');
        }

        $customers = ($isSuperAdmin ? Customer::withoutTenantScope() : Customer::query()->where('tenant_id', $tenant->id))
            ->with(['tenant', 'branch'])
            ->withCount(['appointments', 'sales', 'noteEntries'])
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function (Builder $query) use ($search) {
                    $query->where('customer_code', 'ilike', "%{$search}%")
                        ->orWhere('first_name', 'ilike', "%{$search}%")
                        ->orWhere('last_name', 'ilike', "%{$search}%")
                        ->orWhere('phone', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%");
                });
            })
            ->when($request->filled('branch_id'), fn (Builder $query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('gender'), fn (Builder $query) => $query->where('gender', $request->string('gender')->toString()))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.customer-management.customers.index', [
            'customers' => $customers,
            'branches' => $isSuperAdmin
                ? Branch::withoutTenantScope()->orderBy('name')->get()
                : $tenant->branches()->orderBy('name')->get(),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
            'statuses' => Customer::STATUSES,
            'genders' => Customer::GENDERS,
        ]);
    }

    public function create(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('create', Customer::class);

        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : ($isSuperAdmin ? null : $this->tenant($request));

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'customer_management');
            $entitlements->ensureCanCreate($tenant, 'max_customers', 'Your subscription customer limit has been reached.');
        }

        return view('pages/apps.customer-management.customers.create', $this->formData(new Customer([
            'status' => Customer::STATUS_ACTIVE,
            'marketing_consent' => false,
        ]), $tenant, $isSuperAdmin));
    }

    public function store(StoreCustomerRequest $request, CustomerManagementService $customers, PlanEntitlementService $entitlements): RedirectResponse
    {
        $tenant = $this->tenantForWrite($request);

        $entitlements->ensureFeature($tenant, 'customer_management');
        $entitlements->ensureCanCreate($tenant, 'max_customers', 'Your subscription customer limit has been reached.');

        $customer = $customers->create($tenant, $request->validated(), $request->user());

        return redirect()
            ->route('customer-management.customers.show', $customer)
            ->with('status', 'Customer created successfully.');
    }

    public function show(Request $request, Customer $customer, PlanEntitlementService $entitlements): View
    {
        $this->authorize('view', $customer);
        $entitlements->ensureFeature($customer->tenant, 'customer_management');

        $customer->load(['tenant', 'branch', 'noteEntries.user', 'appointments.branch', 'sales.payments']);
        $customer->loadCount(['appointments', 'sales', 'noteEntries']);

        return view('pages/apps.customer-management.customers.show', [
            'customer' => $customer,
            'totalSpend' => $customer->sales->sum(fn ($sale) => (float) $sale->total),
            'totalPaid' => $customer->sales->sum(fn ($sale) => $sale->payments->sum(fn ($payment) => (float) $payment->amount)),
        ]);
    }

    public function edit(Request $request, Customer $customer, PlanEntitlementService $entitlements): View
    {
        $this->authorize('update', $customer);
        $entitlements->ensureFeature($customer->tenant, 'customer_management');

        return view('pages/apps.customer-management.customers.edit', $this->formData($customer, $customer->tenant, $request->user()->hasRole('Super Admin')));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer, CustomerManagementService $customers): RedirectResponse
    {
        $customer = $customers->update($customer, $request->validated(), $request->user());

        return redirect()
            ->route('customer-management.customers.show', $customer)
            ->with('status', 'Customer updated successfully.');
    }

    public function destroy(Request $request, Customer $customer, CustomerManagementService $customers): RedirectResponse
    {
        $this->authorize('delete', $customer);

        $customers->deactivate($customer);

        return redirect()
            ->route('customer-management.customers.index')
            ->with('status', 'Customer deactivated successfully.');
    }

    private function formData(Customer $customer, ?Tenant $tenant, bool $isSuperAdmin): array
    {
        return [
            'customer' => $customer,
            'branches' => $tenant
                ? Branch::withoutTenantScope()->where('tenant_id', $tenant->id)->where('status', Branch::STATUS_ACTIVE)->orderBy('name')->get()
                : collect(),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'selectedTenant' => $tenant,
            'statuses' => Customer::STATUSES,
            'genders' => Customer::GENDERS,
        ];
    }

    private function tenant(Request $request): Tenant
    {
        $tenant = $request->user()->tenant;

        abort_unless($tenant, 403);

        return $tenant;
    }

    private function tenantForWrite(Request $request): Tenant
    {
        if ($request->user()->hasRole('Super Admin')) {
            return Tenant::query()->findOrFail($request->integer('tenant_id'));
        }

        return $this->tenant($request);
    }
}
