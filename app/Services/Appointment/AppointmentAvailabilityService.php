<?php

namespace App\Services\Appointment;

use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Services\StaffAvailabilityService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class AppointmentAvailabilityService
{
    public function __construct(private readonly StaffAvailabilityService $staffAvailability)
    {
    }

    public function eligibleStaff(int $tenantId, Branch $branch, Service $service)
    {
        return Staff::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->bookable()
            ->whereHas('branches', function (Builder $query) use ($branch) {
                $query->where('branches.id', $branch->id)
                    ->where('staff_branches.status', 'active');
            })
            ->whereHas('services', function (Builder $query) use ($service) {
                $query->where('services.id', $service->id)
                    ->where('staff_services.status', 'active');
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    public function canBook(
        Staff $staff,
        Branch $branch,
        Service $service,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        ?Appointment $ignoreAppointment = null
    ): bool {
        return $this->staffAvailability->canPerformService($staff, $service)
            && $this->staffAvailability->isAvailableAt($staff, $branch, $startsAt, $endsAt)
            && ! $this->hasConflict($staff, $startsAt, $endsAt, $ignoreAppointment);
    }

    public function hasConflict(
        Staff $staff,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        ?Appointment $ignoreAppointment = null
    ): bool {
        return AppointmentService::withoutTenantScope()
            ->where('tenant_id', $staff->tenant_id)
            ->where('staff_id', $staff->id)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->whereHas('appointment', function (Builder $query) use ($ignoreAppointment) {
                $query->whereIn('status', Appointment::ACTIVE_STATUSES);

                if ($ignoreAppointment) {
                    $query->whereKeyNot($ignoreAppointment->id);
                }
            })
            ->exists();
    }
}
