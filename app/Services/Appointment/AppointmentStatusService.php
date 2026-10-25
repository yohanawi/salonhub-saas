<?php

namespace App\Services\Appointment;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentStatusService
{
    private const TRANSITIONS = [
        Appointment::STATUS_PENDING => [
            Appointment::STATUS_CONFIRMED,
            Appointment::STATUS_CANCELLED,
            // An unconfirmed booking the customer never turned up for (used by the automatic no-show job).
            Appointment::STATUS_NO_SHOW,
        ],
        Appointment::STATUS_CONFIRMED => [
            Appointment::STATUS_CHECKED_IN,
            Appointment::STATUS_CANCELLED,
            Appointment::STATUS_NO_SHOW,
        ],
        Appointment::STATUS_CHECKED_IN => [
            Appointment::STATUS_IN_PROGRESS,
            Appointment::STATUS_CANCELLED,
            Appointment::STATUS_NO_SHOW,
        ],
        Appointment::STATUS_IN_PROGRESS => [
            Appointment::STATUS_COMPLETED,
            Appointment::STATUS_CANCELLED,
        ],
    ];

    public function __construct(private readonly CancellationPolicyService $cancellations)
    {
    }

    /**
     * @param  User|null  $user  Null when the system makes the change (the automatic no-show job).
     */
    public function transition(Appointment $appointment, string $status, ?User $user, ?string $notes = null): Appointment
    {
        $from = $appointment->status;
        // Judged before the change, against the time the cancellation is made.
        // Only a booking that has not started yet can be cancelled "late".
        $lateCancellation = $status === Appointment::STATUS_CANCELLED
            && in_array($from, [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED], true)
            && $this->cancellations->isLate($appointment);

        if (! in_array($status, self::TRANSITIONS[$from] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => "Appointment cannot move from {$appointment->status_label} to " . str($status)->replace('_', ' ')->headline() . '.',
            ]);
        }

        return DB::transaction(function () use ($appointment, $status, $user, $notes, $from, $lateCancellation) {
            $payload = [
                'status' => $status,
                'updated_by' => $user?->id,
            ];

            if ($status === Appointment::STATUS_CHECKED_IN) {
                $payload['checked_in_at'] = now();
            }

            if ($status === Appointment::STATUS_IN_PROGRESS) {
                $payload['started_at'] = now();
            }

            if ($status === Appointment::STATUS_COMPLETED) {
                $payload['completed_at'] = now();
            }

            if ($status === Appointment::STATUS_CANCELLED) {
                $payload['cancelled_at'] = now();
                $payload['cancelled_by'] = $user?->id;
                $payload['cancellation_reason'] = $notes;
            }

            $appointment->update($payload);

            $appointment->appointmentServices()->update(['status' => $status]);

            $appointment->statusHistory()->create([
                'tenant_id' => $appointment->tenant_id,
                'from_status' => $from,
                'to_status' => $status,
                'changed_by' => $user?->id,
                'notes' => $notes,
            ]);

            if ($status === Appointment::STATUS_CANCELLED) {
                $this->cancellations->afterCancellation($appointment, $lateCancellation, $user);
            }

            if ($status === Appointment::STATUS_NO_SHOW) {
                $this->cancellations->afterNoShow($appointment, $user);
            }

            return $appointment->fresh(['appointmentServices', 'statusHistory.changedBy']);
        });
    }
}
