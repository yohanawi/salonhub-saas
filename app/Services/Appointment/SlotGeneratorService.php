<?php

namespace App\Services\Appointment;

use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Services\StaffAvailabilityService;
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

        // Rostered shift for the day, or the weekly schedule.
        $window = app(StaffAvailabilityService::class)->workingWindow($staff, $branch->id, $date);

        if (! $window) {
            return [];
        }

        $cursor = CarbonImmutable::parse($date->format('Y-m-d') . ' ' . $window[0]);
        $scheduleEnd = CarbonImmutable::parse($date->format('Y-m-d') . ' ' . $window[1]);
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
