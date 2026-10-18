<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Services\Messaging\CustomerNotifier;

/**
 * Texts the customer when a booking becomes confirmed, whichever screen or service confirmed it.
 * Uses created/updated rather than saved: a save with no changes still fires "saved" and would
 * repeat the previous save's status change.
 */
class AppointmentMessagingObserver
{
    public function __construct(private readonly CustomerNotifier $notifier)
    {
    }

    public function created(Appointment $appointment): void
    {
        $this->confirmIfNeeded($appointment);
    }

    public function updated(Appointment $appointment): void
    {
        if ($appointment->wasChanged('status')) {
            $this->confirmIfNeeded($appointment);
        }
    }

    private function confirmIfNeeded(Appointment $appointment): void
    {
        if ($appointment->status === Appointment::STATUS_CONFIRMED && $appointment->starts_at?->isFuture()) {
            $this->notifier->bookingConfirmation($appointment, auth()->user());
        }
    }
}
