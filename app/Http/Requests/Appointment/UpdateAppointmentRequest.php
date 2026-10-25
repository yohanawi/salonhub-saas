<?php

namespace App\Http\Requests\Appointment;

use App\Models\Appointment;
use Carbon\CarbonImmutable;

class UpdateAppointmentRequest extends StoreAppointmentRequest
{
    public function authorize(): bool
    {
        $appointment = $this->route('appointment');

        return $appointment instanceof Appointment
            && ($this->user()?->can('update', $appointment) ?? false);
    }

    /**
     * Editing notes or services of an appointment that has already passed must not trip the booking window.
     */
    protected function startTimeChanged(CarbonImmutable $startsAt): bool
    {
        return $this->route('appointment')->starts_at?->format('Y-m-d H:i') !== $startsAt->format('Y-m-d H:i');
    }
}
