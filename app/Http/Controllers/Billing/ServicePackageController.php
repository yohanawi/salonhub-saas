<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\StoreServicePackageRequest;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\Tenant;
use App\Services\PlanEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ServicePackageController extends Controller
{
    public function index(Request $request, PlanEntitlementService $entitlements): View
    {
        $tenant = $this->tenant($request, 'service_packages.view', $entitlements);

        return view('pages/apps.billing.service-packages.index', [
            'packages' => ServicePackage::withoutTenantScope()
                ->with('items.service')
                ->withCount('customerPackages')
                ->where('tenant_id', $tenant->id)
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get(),
            'canManage' => $request->user()->can('service_packages.manage'),
            'canSell' => $request->user()->can('service_packages.sell'),
        ]);
    }

    public function create(Request $request, PlanEntitlementService $entitlements): View
    {
        $tenant = $this->tenant($request, 'service_packages.manage', $entitlements);

        return view('pages/apps.billing.service-packages.create', [
            'package' => new ServicePackage(['is_active' => true]),
            'services' => $this->services($tenant),
        ]);
    }

    public function store(StoreServicePackageRequest $request): RedirectResponse
    {
        $package = DB::transaction(function () use ($request) {
            $data = $request->validated();

            $package = ServicePackage::create($this->payload($data) + [
                'tenant_id' => $request->user()->tenant_id,
                'created_by' => $request->user()->id,
            ]);

            $this->syncItems($package, $data['items']);

            return $package;
        });

        return redirect()
            ->route('billing.service-packages.index')
            ->with('status', 'Package ' . $package->name . ' created.');
    }

    public function edit(Request $request, ServicePackage $servicePackage, PlanEntitlementService $entitlements): View
    {
        $tenant = $this->tenant($request, 'service_packages.manage', $entitlements);
        abort_unless((int) $servicePackage->tenant_id === (int) $tenant->id, 404);

        return view('pages/apps.billing.service-packages.edit', [
            'package' => $servicePackage->load('items'),
            'services' => $this->services($tenant),
        ]);
    }

    /**
     * Customers who already bought the package keep the credits they paid for; edits only affect new sales.
     */
    public function update(StoreServicePackageRequest $request, ServicePackage $servicePackage): RedirectResponse
    {
        abort_unless((int) $servicePackage->tenant_id === (int) $request->user()->tenant_id, 404);

        DB::transaction(function () use ($request, $servicePackage) {
            $data = $request->validated();

            $servicePackage->update($this->payload($data));
            $this->syncItems($servicePackage, $data['items']);
        });

        return redirect()
            ->route('billing.service-packages.index')
            ->with('status', 'Package ' . $servicePackage->name . ' updated.');
    }

    private function payload(array $data): array
    {
        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'validity_days' => $data['validity_days'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }

    private function syncItems(ServicePackage $package, array $items): void
    {
        $package->items()->delete();

        foreach ($items as $item) {
            $package->items()->create([
                'tenant_id' => $package->tenant_id,
                'service_id' => $item['service_id'],
                'quantity' => $item['quantity'],
            ]);
        }
    }

    private function services(Tenant $tenant)
    {
        return Service::withoutTenantScope()->where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('name')->get();
    }

    private function tenant(Request $request, string $permission, PlanEntitlementService $entitlements): Tenant
    {
        $user = $request->user();
        abort_unless($user->tenant_id && $user->can($permission), 403);
        $entitlements->ensureFeature($user->tenant, 'pos_billing');

        return $user->tenant;
    }
}
