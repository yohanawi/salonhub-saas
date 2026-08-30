<?php

namespace App\Services\Appointment;

use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class SlotGeneratorService
{
    public function __construct(
        private readonly AppointmentAvailabilityService $availability,
        private readonly AppointmentPricingService $pricing
    ) {
    }

    public function slots(Branch $branch, Service $service, Staff $staff, CarbonInterface|string $date, int $intervalMinutes = 15): array
    {
        $date = CarbonImmutable::parse($date)->startOfDay();
        $snapshot = $this->pricing->snapshot($service, $branch, $staff);
        $duration = (int) $snapshot['duration_minutes'];

        $schedule = $staff->schedules()
            ->where('branch_id', $branch->id)
            ->where('day_of_week', $date->dayOfWeekIso)
            ->where('is_working', true)
            ->orderBy('start_time')
            ->first();

        if (! $schedule) {
            return [];
        }

        $cursor = CarbonImmutable::parse($date->format('Y-m-d') . ' ' . $schedule->start_time);
        $scheduleEnd = CarbonImmutable::parse($date->format('Y-m-d') . ' ' . $schedule->end_time);
        $slots = [];

        while ($cursor->copy()->addMinutes($duration)->lessThanOrEqualTo($scheduleEnd)) {
            $end = $cursor->addMinutes($duration);

            if ($this->availability->canBook($staff, $branch, $service, $cursor, $end)) {
                $slots[] = [
                    'time' => $cursor->format('H:i'),
                    'label' => $cursor->format('h:i A'),
                    'starts_at' => $cursor->toDateTimeString(),
                    'ends_at' => $end->toDateTimeString(),
                ];
            }

            $cursor = $cursor->addMinutes($intervalMinutes);
        }

        return $slots;
    }
}
