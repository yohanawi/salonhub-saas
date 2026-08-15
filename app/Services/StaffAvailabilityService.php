<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use Carbon\CarbonInterface;

class StaffAvailabilityService
{
    public function canWorkAtBranch(Staff $staff, Branch|int $branch): bool
    {
        $branchId = $branch instanceof Branch ? $branch->id : $branch;

        return $staff->branches()
            ->where('branches.id', $branchId)
            ->wherePivot('status', 'active')
            ->exists();
    }

    public function canPerformService(Staff $staff, Service|int $service): bool
    {
        $serviceId = $service instanceof Service ? $service->id : $service;

        return $staff->services()
            ->where('services.id', $serviceId)
            ->wherePivot('status', 'active')
            ->exists();
    }

    public function isAvailableAt(Staff $staff, Branch|int $branch, CarbonInterface $startsAt, CarbonInterface $endsAt): bool
    {
        if ($staff->status !== Staff::STATUS_ACTIVE || ! $staff->is_bookable) {
            return false;
        }

        if (! $this->canWorkAtBranch($staff, $branch)) {
            return false;
        }

        $branchId = $branch instanceof Branch ? $branch->id : $branch;
        $dayOfWeek = $startsAt->dayOfWeekIso;
        $startTime = $startsAt->format('H:i:s');
        $endTime = $endsAt->format('H:i:s');

        $hasWorkingShift = $staff->schedules()
            ->where('branch_id', $branchId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_working', true)
            ->where('start_time', '<=', $startTime)
            ->where('end_time', '>=', $endTime)
            ->exists();

        if (! $hasWorkingShift) {
            return false;
        }

        $overlapsBreak = $staff->breaks()
            ->where('branch_id', $branchId)
            ->where('day_of_week', $dayOfWeek)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->exists();

        if ($overlapsBreak) {
            return false;
        }

        return ! $staff->timeOff()
            ->where(function ($query) use ($branchId) {
                $query->whereNull('branch_id')
                    ->orWhere('branch_id', $branchId);
            })
            ->whereIn('status', ['approved', 'pending'])
            ->where('start_datetime', '<', $endsAt)
            ->where('end_datetime', '>', $startsAt)
            ->exists();
    }
}
