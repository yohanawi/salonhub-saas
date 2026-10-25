<?php

namespace App\Services\Appointment;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Tenant;
use App\Services\Settings\SettingService;
use Illuminate\Validation\ValidationException;

/**
 * Marks bookings as no-shows once the customer is later than the grace period and was never checked in,
 * for salons that turn on "Auto Mark No-show". The usual no-show rules (deposit kept, etc.) then apply.
 */
class AutoNoShowService
{
    // Only recent bookings are touched, so switching the setting on never rewrites old history.
    private const LOOKBACK_DAYS = 2;

    public function __construct(
        private readonly SettingService $settings,
        private readonly BookingWindowService $window,
        private readonly AppointmentStatusService $statuses,
    ) {
    }

    public function run(?int $tenantId = null): int
    {
        $marked = 0;

        Branch::withoutTenantScope()
            ->with('tenant')
            ->whereIn('tenant_id', Tenant::query()->operational()->when($tenantId, fn ($query) => $query->whereKey($tenantId))->select('id'))
            ->where('status', Branch::STATUS_ACTIVE)
            ->each(function (Branch $branch) use (&$marked) {
                $marked += $this->runForBranch($branch);
            });

        return $marked;
    }

    private function runForBranch(Branch $branch): int
    {
        if (! $this->settings->get('appointment_status.auto_mark_no_show', $branch->tenant_id, $branch->id)) {
            return 0;
        }

        $grace = max(0, (int) $this->settings->get('appointment_status.no_show_grace_minutes', $branch->tenant_id, $branch->id));
        $now = $this->window->now($branch);
        $marked = 0;

        Appointment::withoutTenantScope()
            ->where('branch_id', $branch->id)
            ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED])
            ->where('starts_at', '<=', $now->subMinutes($grace))
            ->where('starts_at', '>=', $now->subDays(self::LOOKBACK_DAYS))
            ->lazyById(100)
            ->each(function (Appointment $appointment) use ($grace, &$marked) {
                try {
                    $this->statuses->transition($appointment, Appointment::STATUS_NO_SHOW, null, "Marked as a no-show automatically ({$grace} minutes after the start time, not checked in).");
                    $marked++;
                } catch (ValidationException) {
                    // Changed by someone at the desk in the meantime.
                }
            });

        return $marked;
    }
}
