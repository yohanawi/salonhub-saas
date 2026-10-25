<?php

namespace App\Services\Appointment;

use App\Models\Branch;
use App\Services\Settings\SettingService;
use Carbon\CarbonImmutable;

/**
 * The salon's booking window: no past times, no further ahead than the maximum advance period, and for online
 * bookings the minimum notice and same-day rules. Staff may book a walk-in that started up to one booking
 * interval ago, since the slot picker rounds to the interval.
 */
class BookingWindowService
{
    public function __construct(private readonly SettingService $settings)
    {
    }

    /**
     * @return string|null Why the time cannot be booked, or null when it can.
     */
    public function violation(Branch $branch, CarbonImmutable $startsAt, ?string $bookingSource = null): ?string
    {
        $setting = fn (string $key) => $this->settings->get($key, $branch->tenant_id, $branch->id);
        $now = $this->now($branch);

        if ($startsAt->lt($now->subMinutes(max(0, (int) $setting('booking.interval_minutes'))))) {
            return 'The appointment time is in the past.';
        }

        $maximumDays = (int) $setting('booking.maximum_advance_days');

        if ($maximumDays > 0 && $startsAt->gt($now->addDays($maximumDays)->endOfDay())) {
            return "Appointments can be booked at most {$maximumDays} days in advance.";
        }

        if ($bookingSource !== 'online') {
            return null;
        }

        if (! $setting('booking.allow_same_day') && $startsAt->isSameDay($now)) {
            return 'Same-day online bookings are not allowed.';
        }

        $noticeMinutes = (int) $setting('booking.minimum_notice_minutes');

        return $startsAt->lt($now->addMinutes($noticeMinutes))
            ? "Online bookings need at least {$noticeMinutes} minutes' notice."
            : null;
    }

    /**
     * Appointment times are stored as the branch's wall-clock time, so "now" is read the same way.
     */
    public function now(Branch $branch): CarbonImmutable
    {
        $timezone = $branch->timezone ?: ($branch->tenant?->timezone ?: config('app.timezone'));

        return CarbonImmutable::parse(now($timezone)->format('Y-m-d H:i:s'));
    }

    public function intervalMinutes(Branch $branch): int
    {
        return max(5, (int) $this->settings->get('booking.interval_minutes', $branch->tenant_id, $branch->id));
    }
}
