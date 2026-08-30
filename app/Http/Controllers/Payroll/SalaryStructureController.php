<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\StoreSalaryStructureRequest;
use App\Http\Requests\Payroll\UpdateSalaryStructureRequest;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\StaffSalaryStructure;
use App\Models\Tenant;
use App\Services\Payroll\SalaryStructureService;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalaryStructureController extends Controller
{
    public function index(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('viewAny', StaffSalaryStructure::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'payroll');
        }

        $structures = ($isSuperAdmin ? StaffSalaryStructure::withoutTenantScope() : StaffSalaryStructure::query()->where('tenant_id', $tenant->id))
            ->with(['tenant', 'branch', 'staff'])
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->latest('effective_from')
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.payroll.salary-structures.index', [
            'structures' => $structures,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function create(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('create', StaffSalaryStructure::class);
        [$isSuperAdmin, $tenant] = $this->tenantContext($request, true);

        if ($tenant) {
            $entitlements->ensureFeature($tenant, 'payroll');
        }

        return view('pages/apps.payroll.salary-structures.create', $this->formData($tenant, $isSuperAdmin) + [
            'structure' => new StaffSalaryStructure([
                'salary_type' => StaffSalaryStructure::TYPE_SALARY_COMMISSION,
                'basic_salary' => 0,
                'commission_enabled' => true,
                'payroll_frequency' => 'monthly',
                'effective_from' => today(),
                'status' => StaffSalaryStructure::STATUS_ACTIVE,
            ]),
        ]);
    }

    public function store(StoreSalaryStructureRequest $request, SalaryStructureService $structures, PlanEntitlementService $entitlements): RedirectResponse
    {
        $tenant = $this->tenantForWrite($request);
        $entitlements->ensureFeature($tenant, 'payroll');
        $structure = $structures->create($tenant, $request->validated(), $request->user());

        return redirect()->route('payroll.salary-structures.edit', $structure)->with('status', 'Salary structure saved successfully.');
    }

    public function edit(StaffSalaryStructure $salaryStructure, PlanEntitlementService $entitlements): View
    {
        $this->authorize('update', $salaryStructure);
        $entitlements->ensureFeature($salaryStructure->tenant, 'payroll');

        return view('pages/apps.payroll.salary-structures.edit', $this->formData($salaryStructure->tenant, false) + [
            'structure' => $salaryStructure,
            'selectedTenant' => $salaryStructure->tenant,
        ]);
    }

    public function update(UpdateSalaryStructureRequest $request, StaffSalaryStructure $salaryStructure, SalaryStructureService $structures, PlanEntitlementService $entitlements): RedirectResponse
    {
        $entitlements->ensureFeature($salaryStructure->tenant, 'payroll');
        $structures->update($salaryStructure, $request->validated(), $request->user());

        return redirect()->route('payroll.salary-structures.index')->with('status', 'Salary structure updated successfully.');
    }

    private function tenantContext(Request $request, bool $forCreate = false): array
    {
        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : ($isSuperAdmin ? null : $request->user()->tenant);

        return [$isSuperAdmin, $tenant];
    }

    private function tenantForWrite(Request $request): Tenant
    {
        return $request->user()->hasRole('Super Admin') ? Tenant::query()->findOrFail($request->integer('tenant_id')) : $request->user()->tenant;
    }

    private function formData(?Tenant $tenant, bool $isSuperAdmin): array
    {
        return [
            'branches' => $tenant ? Branch::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('name')->get() : collect(),
            'staffMembers' => $tenant ? Staff::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('first_name')->get() : collect(),
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'selectedTenant' => $tenant,
            'isSuperAdmin' => $isSuperAdmin,
        ];
    }
}
