<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\StorePaymentMethodRequest;
use App\Http\Requests\Billing\UpdatePaymentMethodRequest;
use App\Models\PaymentMethod;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', PaymentMethod::class);

        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : ($isSuperAdmin ? null : $request->user()->tenant);

        $paymentMethods = ($isSuperAdmin ? PaymentMethod::withoutTenantScope() : PaymentMethod::query()->where('tenant_id', $tenant->id))
            ->with('tenant')
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn ($query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.billing.payment-methods.index', [
            'paymentMethods' => $paymentMethods,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
            'types' => PaymentMethod::TYPES,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', PaymentMethod::class);

        return view('pages/apps.billing.payment-methods.create', [
            'paymentMethod' => new PaymentMethod([
                'type' => PaymentMethod::TYPE_CASH,
                'is_active' => true,
                'requires_reference' => false,
                'sort_order' => 0,
            ]),
            'tenants' => $request->user()->hasRole('Super Admin') ? Tenant::query()->orderBy('name')->get() : collect(),
            'selectedTenant' => $request->user()->hasRole('Super Admin') && $request->filled('tenant_id') ? Tenant::query()->find($request->integer('tenant_id')) : null,
            'types' => PaymentMethod::TYPES,
        ]);
    }

    public function store(StorePaymentMethodRequest $request): RedirectResponse
    {
        $tenantId = $request->user()->hasRole('Super Admin')
            ? $request->integer('tenant_id')
            : $request->user()->tenant_id;

        PaymentMethod::create($this->payload($request->validated(), $tenantId));

        return redirect()
            ->route('billing.payment-methods.index')
            ->with('status', 'Payment method created successfully.');
    }

    public function edit(Request $request, PaymentMethod $paymentMethod): View
    {
        $this->authorize('update', $paymentMethod);

        return view('pages/apps.billing.payment-methods.edit', [
            'paymentMethod' => $paymentMethod,
            'tenants' => collect(),
            'selectedTenant' => $paymentMethod->tenant,
            'types' => PaymentMethod::TYPES,
        ]);
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $paymentMethod->update($this->payload($request->validated(), $paymentMethod->tenant_id));

        return redirect()
            ->route('billing.payment-methods.index')
            ->with('status', 'Payment method updated successfully.');
    }

    private function payload(array $data, int $tenantId): array
    {
        return [
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'code' => str($data['code'])->slug('_')->toString(),
            'type' => $data['type'],
            'is_active' => (bool) ($data['is_active'] ?? false),
            'requires_reference' => (bool) ($data['requires_reference'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }
}
