<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffShift;
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

    /**
     * Working hours for a staff member at a branch on a date, as ['H:i:s', 'H:i:s'], or null when not working.
     * A rostered shift for that date wins over the weekly schedule; a shift at another branch, or a rostered
     * day off, means the staff member is not working at this branch that day.
     */
    public function workingWindow(Staff $staff, int $branchId, CarbonInterface $date): ?array
    {
        $shift = StaffShift::withoutTenantScope()
            ->where('staff_id', $staff->id)
            ->whereDate('shift_date', $date->toDateString())
            ->first();

        if ($shift) {
            if ($shift->is_off || (int) $shift->branch_id !== $branchId || ! $shift->start_time || ! $shift->end_time) {
                return null;
            }

            return [$this->time($shift->start_time), $this->time($shift->end_time)];
        }

        $schedule = $staff->schedules()
            ->where('branch_id', $branchId)
            ->where('day_of_week', $date->dayOfWeekIso)
            ->where('is_working', true)
            ->orderBy('start_time')
            ->first();

        return $schedule && $schedule->getRawOriginal('start_time') && $schedule->getRawOriginal('end_time')
            ? [$this->time($schedule->getRawOriginal('start_time')), $this->time($schedule->getRawOriginal('end_time'))]
            : null;
    }

    private function time(string $value): string
    {
        return strlen($value) === 5 ? $value . ':00' : substr($value, 0, 8);
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
        $window = $this->workingWindow($staff, $branchId, $startsAt);

        if (! $window || $startTime < $window[0] || $endTime > $window[1]) {
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
