<?php

namespace App\Services\Appointment;

use App\Models\Appointment;
use App\Models\AppointmentDeposit;
use App\Models\User;
use App\Services\Billing\DepositService;
use App\Services\Settings\SettingService;

/**
 * The salon's cancellation rules: a cancellation inside the cut-off is "late", and a late cancellation or a
 * no-show keeps (forfeits) the customer's held deposit when the salon's settings say so.
 */
class CancellationPolicyService
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly BookingWindowService $window,
        private readonly DepositService $deposits,
    ) {
    }

    public function cutoffHours(Appointment $appointment): int
    {
        return max(0, (int) $this->settings->get('booking.cancellation_cutoff_hours', $appointment->tenant_id, $appointment->branch_id));
    }

    /**
     * Whether cancelling now would fall inside the cut-off before the appointment starts.
     */
    public function isLate(Appointment $appointment): bool
    {
        $cutoff = $this->cutoffHours($appointment);
        $appointment->loadMissing('branch.tenant');

        if ($cutoff === 0 || ! $appointment->starts_at || ! $appointment->branch) {
            return false;
        }

        return $this->window->now($appointment->branch)->gt($appointment->starts_at->copy()->subHours($cutoff));
    }

    /**
     * Called once an appointment has been cancelled. Returns the deposit amount kept, if any.
     */
    public function afterCancellation(Appointment $appointment, bool $late, ?User $user): float
    {
        if (! $late) {
            return 0.0;
        }

        $appointment->forceFill(['cancelled_late' => true])->saveQuietly();

        return $this->setting($appointment, 'booking.forfeit_deposit_on_late_cancellation')
            ? $this->forfeitHeld($appointment, 'Late cancellation (inside the ' . $this->cutoffHours($appointment) . '-hour cut-off).', $user)
            : 0.0;
    }

    /**
     * Called once an appointment has been marked as a no-show. Returns the deposit amount kept, if any.
     */
    public function afterNoShow(Appointment $appointment, ?User $user): float
    {
        return $this->setting($appointment, 'booking.forfeit_deposit_on_no_show')
            ? $this->forfeitHeld($appointment, 'Customer did not show up.', $user)
            : 0.0;
    }

    private function forfeitHeld(Appointment $appointment, string $reason, ?User $user): float
    {
        $kept = 0.0;

        AppointmentDeposit::withoutTenantScope()
            ->where('appointment_id', $appointment->id)
            ->where('status', AppointmentDeposit::STATUS_HELD)
            ->get()
            ->each(function (AppointmentDeposit $deposit) use ($reason, $user, &$kept) {
                $kept += $deposit->remainingAmount();
                $this->deposits->forfeit($deposit, ['reason' => $reason], $user);
            });

        return round($kept, 2);
    }

    private function setting(Appointment $appointment, string $key): bool
    {
        return (bool) $this->settings->get($key, $appointment->tenant_id, $appointment->branch_id);
    }
}
