<?php

namespace App\Services\Appointment;

use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Staff;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Calendar drag-and-drop: moving an appointment to another time (and optionally another staff member), and
 * stretching or shrinking a service. Every new time goes through the same availability and booking-window
 * checks as a normal booking.
 */
class AppointmentRescheduleService
{
    public const MIN_DURATION_MINUTES = 5;

    public function __construct(
        private readonly AppointmentAvailabilityService $availability,
        private readonly AppointmentPricingService $pricing,
        private readonly BookingWindowService $window,
        private readonly AppointmentBookingService $bookings,
    ) {
    }

    /**
     * Shifts every service of the appointment by the same amount. When $fromStaffId and $toStaffId differ, the
     * services $fromStaffId was doing move to $toStaffId and are re-priced for them; their times stay as dragged.
     */
    public function move(Appointment $appointment, CarbonImmutable $startsAt, ?int $fromStaffId, ?int $toStaffId, User $user): Appointment
    {
        return $this->reschedule($appointment, $user, function (Collection $lines, Appointment $appointment) use ($startsAt, $fromStaffId, $toStaffId) {
            $this->ensureWindow($appointment, $startsAt);

            $shift = (int) $appointment->starts_at->diffInMinutes($startsAt, false);
            $toStaff = null;

            if ($toStaffId && $fromStaffId && $toStaffId !== $fromStaffId) {
                $toStaff = Staff::withoutTenantScope()->where('tenant_id', $appointment->tenant_id)->find($toStaffId);

                if (! $toStaff || ! $lines->contains('staff_id', $fromStaffId)) {
                    throw ValidationException::withMessages(['staff_id' => 'Choose a staff member from this salon.']);
                }

                $this->bookings->ensureStaffReassignmentAllowed(
                    $appointment,
                    $lines->pluck('staff_id')->map(fn ($id) => (int) $id === $fromStaffId ? $toStaffId : (int) $id)->all()
                );
            }

            foreach ($lines as $line) {
                $line->starts_at = $line->starts_at->copy()->addMinutes($shift);
                $line->ends_at = $line->ends_at->copy()->addMinutes($shift);

                if ($toStaff && (int) $line->staff_id === $fromStaffId) {
                    $snapshot = $this->pricing->snapshot($line->service, $appointment->branch, $toStaff, (float) $line->discount_amount);
                    $line->staff_id = $toStaff->id;
                    $line->setRelation('staff', $toStaff);
                    $line->unit_price = $snapshot['unit_price'];
                    $line->price = $snapshot['unit_price'];
                    $line->discount_amount = $snapshot['discount_amount'];
                    $line->total_price = $snapshot['total_price'];
                }
            }

            return 'Moved from ' . $appointment->starts_at->format('d M Y h:i A') . ' to ' . $startsAt->format('d M Y h:i A') . '.';
        });
    }

    /**
     * Changes when $staffId's last service in the appointment ends. Services after it move by the same amount,
     * so a stretched service never overlaps the next one.
     */
    public function resize(Appointment $appointment, int $staffId, CarbonImmutable $endsAt, User $user): Appointment
    {
        return $this->reschedule($appointment, $user, function (Collection $lines, Appointment $appointment) use ($staffId, $endsAt) {
            $target = $lines->where('staff_id', $staffId)->sortBy('ends_at')->last();

            if (! $target) {
                throw ValidationException::withMessages(['staff_id' => 'This staff member has no service in the appointment.']);
            }

            $duration = (int) $target->starts_at->diffInMinutes($endsAt, false);

            if ($duration < self::MIN_DURATION_MINUTES) {
                throw ValidationException::withMessages(['ends_at' => 'A service must last at least ' . self::MIN_DURATION_MINUTES . ' minutes.']);
            }

            $shift = (int) $target->ends_at->diffInMinutes($endsAt, false);
            $originalEnd = $target->ends_at->copy();

            foreach ($lines as $line) {
                if ($line->is($target)) {
                    $line->ends_at = $endsAt;
                    $line->duration_minutes = $duration;
                } elseif ($line->starts_at->gte($originalEnd)) {
                    $line->starts_at = $line->starts_at->copy()->addMinutes($shift);
                    $line->ends_at = $line->ends_at->copy()->addMinutes($shift);
                }
            }

            return "{$target->service_name} now ends at " . $endsAt->format('h:i A') . " ({$duration} min).";
        });
    }

    /**
     * @param  callable(Collection<int, AppointmentService>, Appointment): string  $change  Adjusts the lines in memory and describes the change.
     */
    private function reschedule(Appointment $appointment, User $user, callable $change): Appointment
    {
        return DB::transaction(function () use ($appointment, $user, $change) {
            $appointment = Appointment::withoutTenantScope()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            $appointment->load(['branch.tenant', 'appointmentServices.service', 'appointmentServices.staff']);

            if (! in_array($appointment->status, [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED], true)) {
                throw ValidationException::withMessages(['appointment' => 'Only pending and confirmed appointments can be rescheduled.']);
            }

            $lines = $appointment->appointmentServices
                ->reject(fn (AppointmentService $line) => in_array($line->status, [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW], true))
                ->sortBy('starts_at')
                ->values();

            if ($lines->isEmpty()) {
                throw ValidationException::withMessages(['appointment' => 'This appointment has no services to move.']);
            }

            // The stored discount is line discounts plus any appointment-wide discount; keep the latter as is.
            $appointmentDiscount = max(0, (float) $appointment->discount_amount - (float) $lines->sum('discount_amount'));

            $description = $change($lines, $appointment);

            // Locked in id order (as in AppointmentBookingService) so concurrent bookings cannot deadlock.
            Staff::withoutTenantScope()->whereIn('id', $lines->pluck('staff_id')->unique()->sort()->values())->lockForUpdate()->get();

            foreach ($lines as $line) {
                if (! $this->availability->canBook($line->staff, $appointment->branch, $line->service, $line->starts_at, $line->ends_at, $appointment)) {
                    throw ValidationException::withMessages([
                        'staff_id' => "{$line->staff->full_name} is not available for {$line->service_name} at " . $line->starts_at->format('h:i A') . '.',
                    ]);
                }
            }

            $lines->each->save();

            $totals = $this->pricing->totals(
                $lines->map(fn (AppointmentService $line) => ['unit_price' => $line->unit_price, 'discount_amount' => $line->discount_amount])->all(),
                $appointmentDiscount,
                (float) $appointment->tax_amount
            );

            $appointment->update([
                'starts_at' => $lines->min('starts_at'),
                'ends_at' => $lines->max('ends_at'),
                'staff_id' => $lines->first()->staff_id,
                ...$totals,
                'updated_by' => $user->id,
            ]);

            $appointment->statusHistory()->create([
                'tenant_id' => $appointment->tenant_id,
                'from_status' => $appointment->status,
                'to_status' => $appointment->status,
                'changed_by' => $user->id,
                'notes' => 'Rescheduled on the calendar. ' . $description,
            ]);

            return $appointment->fresh(['appointmentServices.staff']);
        });
    }

    private function ensureWindow(Appointment $appointment, CarbonImmutable $startsAt): void
    {
        if ($violation = $this->window->violation($appointment->branch, $startsAt, $appointment->booking_source)) {
            throw ValidationException::withMessages(['starts_at' => $violation]);
        }
    }
}
