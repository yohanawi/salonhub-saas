<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StoreStaffRequest;
use App\Http\Requests\Staff\UpdateStaffRequest;
use App\Models\Branch;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Models\StaffCommissionSetting;
use App\Models\StaffTimeOff;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BranchContext;
use App\Services\PlanEntitlementService;
use App\Services\StaffManagementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request, BranchContext $branchContext): View
    {
        $this->authorize('viewAny', Staff::class);

        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin ? null : $this->tenant($request);
        $visibleBranches = $isSuperAdmin
            ? Branch::withoutTenantScope()->orderBy('name')->get()
            : $branchContext->availableBranches($request->user());
        $hasTenantWideAccess = $isSuperAdmin || $branchContext->hasTenantWideBranchAccess($request->user());

        $staff = ($isSuperAdmin ? Staff::withoutTenantScope() : Staff::query()->where('tenant_id', $tenant->id))
            ->with(['tenant', 'branches', 'services'])
            ->withCount(['branches', 'services'])
            ->when($isSuperAdmin && $request->filled('tenant_id'), fn (Builder $query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->when(! $hasTenantWideAccess, function (Builder $query) use ($visibleBranches) {
                $query->whereHas('branches', fn (Builder $query) => $query->whereIn('branches.id', $visibleBranches->pluck('id')));
            })
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function (Builder $query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('bookable'), fn (Builder $query) => $query->where('is_bookable', $request->string('bookable')->toString() === 'yes'))
            ->when($request->filled('branch_id'), function (Builder $query) use ($request, $visibleBranches, $hasTenantWideAccess) {
                $branchId = $request->integer('branch_id');

                if ($hasTenantWideAccess || $visibleBranches->contains('id', $branchId)) {
                    $query->whereHas('branches', fn (Builder $query) => $query->where('branches.id', $branchId));
                }
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(15)
            ->withQueryString();

        return view('pages/apps.staff-management.staff.index', [
            'staff' => $staff,
            'branches' => $visibleBranches,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function create(Request $request, PlanEntitlementService $entitlements): View
    {
        $this->authorize('create', Staff::class);

        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        $tenant = $isSuperAdmin && $request->filled('tenant_id')
            ? Tenant::query()->findOrFail($request->integer('tenant_id'))
            : ($isSuperAdmin ? null : $this->tenant($request));

        if ($tenant) {
            $entitlements->ensureCanCreate($tenant, 'max_staff', 'Your subscription staff limit has been reached.');
        }

        return view('pages/apps.staff-management.staff.create', $this->formData($request, new Staff([
            'status' => Staff::STATUS_ACTIVE,
            'is_bookable' => true,
            'show_online' => false,
            'commission_type' => StaffCommissionSetting::TYPE_PERCENTAGE,
            'commission_value' => 0,
        ]), $tenant, $isSuperAdmin));
    }

    public function store(StoreStaffRequest $request, StaffManagementService $staffService, PlanEntitlementService $entitlements): RedirectResponse
    {
        $tenant = $this->tenantForWrite($request);
        $entitlements->ensureCanCreate($tenant, 'max_staff', 'Your subscription staff limit has been reached.');

        $staff = $staffService->create($tenant, $request->validated());

        return redirect()
            ->route('staff-management.staff.show', $staff)
            ->with('status', 'Staff member created successfully.');
    }

    public function show(Request $request, Staff $staff): View
    {
        $this->authorize('view', $staff);

        $staff->load(['tenant', 'user', 'branches', 'services.category', 'schedules.branch', 'breaks.branch', 'timeOff.branch', 'commissionSettings.service']);

        return view('pages/apps.staff-management.staff.show', [
            'staffMember' => $staff,
        ]);
    }

    public function edit(Request $request, Staff $staff): View
    {
        $this->authorize('update', $staff);

        $staff->load(['branches', 'services', 'schedules', 'breaks', 'timeOff', 'commissionSettings']);

        return view('pages/apps.staff-management.staff.edit', $this->formData($request, $staff, $staff->tenant, $request->user()->hasRole('Super Admin')));
    }

    public function update(UpdateStaffRequest $request, Staff $staff, StaffManagementService $staffService): RedirectResponse
    {
        $staff = $staffService->update($staff, $request->validated());

        return redirect()
            ->route('staff-management.staff.show', $staff)
            ->with('status', 'Staff member updated successfully.');
    }

    public function destroy(Request $request, Staff $staff, StaffManagementService $staffService): RedirectResponse
    {
        $this->authorize('delete', $staff);

        $staffService->deactivate($staff);

        return redirect()
            ->route('staff-management.staff.index')
            ->with('status', 'Staff member deactivated successfully.');
    }

    private function formData(Request $request, Staff $staff, ?Tenant $tenant, bool $isSuperAdmin): array
    {
        $branches = $tenant
            ? Branch::withoutTenantScope()->where('tenant_id', $tenant->id)->where('status', Branch::STATUS_ACTIVE)->orderBy('name')->get()
            : collect();
        $services = $tenant
            ? Service::withoutTenantScope()->with('category')->where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('name')->get()
            : collect();
        $users = $tenant
            ? User::query()->where('tenant_id', $tenant->id)->orderBy('name')->get()
            : collect();

        return [
            'staffMember' => $staff,
            'branches' => $branches,
            'services' => $services,
            'serviceCategories' => $tenant
                ? ServiceCategory::withoutTenantScope()->where('tenant_id', $tenant->id)->orderBy('name')->get()
                : collect(),
            'users' => $users,
            'tenants' => $isSuperAdmin ? Tenant::query()->orderBy('name')->get() : collect(),
            'selectedTenant' => $tenant,
            'statuses' => Staff::STATUSES,
            'genders' => Staff::GENDERS,
            'timeOffTypes' => StaffTimeOff::TYPES,
            'commissionTypes' => StaffCommissionSetting::TYPES,
            'days' => Branch::DAY_LABELS,
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
