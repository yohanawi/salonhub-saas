<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Services\Appointment\AppointmentAvailabilityService;
use App\Services\Appointment\SlotGeneratorService;
use App\Services\BranchContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentAvailabilityController extends Controller
{
    public function staff(Request $request, BranchContext $branchContext, AppointmentAvailabilityService $availability): JsonResponse
    {
        $this->authorize('create', Appointment::class);

        $data = $request->validate([
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'branch_id' => ['required', 'integer'],
            'service_id' => ['required', 'integer'],
        ]);

        [$tenant, $branch, $service] = $this->resolveContext($request, $branchContext, $data);

        return response()->json([
            'data' => $availability->eligibleStaff($tenant->id, $branch, $service)
                ->map(fn (Staff $staff) => [
                    'id' => $staff->id,
                    'name' => $staff->full_name,
                    'job_title' => $staff->job_title,
                ])
                ->values(),
        ]);
    }

    public function slots(Request $request, BranchContext $branchContext, SlotGeneratorService $slots): JsonResponse
    {
        $this->authorize('create', Appointment::class);

        $data = $request->validate([
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'branch_id' => ['required', 'integer'],
            'service_id' => ['required', 'integer'],
            'staff_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
        ]);

        [, $branch, $service] = $this->resolveContext($request, $branchContext, $data);
        $staff = Staff::withoutTenantScope()
            ->where('tenant_id', $branch->tenant_id)
            ->findOrFail($data['staff_id']);

        return response()->json([
            'data' => $slots->slots($branch, $service, $staff, $data['date']),
        ]);
    }

    private function resolveContext(Request $request, BranchContext $branchContext, array $data): array
    {
        $tenant = $request->user()->hasRole('Super Admin')
            ? Tenant::query()->findOrFail($data['tenant_id'])
            : $request->user()->tenant;

        abort_unless($tenant, 403);

        $branch = Branch::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('status', Branch::STATUS_ACTIVE)
            ->findOrFail($data['branch_id']);

        abort_if(! $request->user()->hasRole('Super Admin') && ! $branchContext->canAccess($request->user(), $branch), 403);

        $service = Service::withoutTenantScope()
            ->with(['branches', 'staff'])
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->findOrFail($data['service_id']);

        abort_unless($service->isAvailableAt($branch), 422, 'This service is not available at the selected branch.');

        return [$tenant, $branch, $service];
    }
}
