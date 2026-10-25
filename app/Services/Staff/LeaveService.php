<?php

namespace App\Services\Staff;

use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Staff;
use App\Models\StaffTimeOff;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Leave requests live in staff_time_off. A pending request already blocks new bookings (so nobody is booked
 * into a day that is about to be approved); rejecting or cancelling it frees the time again.
 */
class LeaveService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public function request(Staff $staff, array $data, User $user, bool $approveNow = false): StaffTimeOff
    {
        $timezone = $staff->tenant?->timezone ?: config('app.timezone');
        $start = CarbonImmutable::parse($data['start_date'], $timezone)->startOfDay();
        $end = CarbonImmutable::parse($data['end_date'] ?? $data['start_date'], $timezone)->endOfDay();

        if (! empty($data['half_day'])) {
            $end = $start->setTime(13, 0);
        }

        if ($end->lessThanOrEqualTo($start)) {
            throw ValidationException::withMessages(['end_date' => 'The leave must end on or after the day it starts.']);
        }

        $overlap = StaffTimeOff::withoutTenantScope()
            ->where('staff_id', $staff->id)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_APPROVED])
            ->where('start_datetime', '<', $end->toDateTimeString())
            ->where('end_datetime', '>', $start->toDateTimeString())
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages(['start_date' => 'There is already a leave request covering these dates.']);
        }

        return StaffTimeOff::withoutTenantScope()->create([
            'tenant_id' => $staff->tenant_id,
            'staff_id' => $staff->id,
            'branch_id' => null,
            'start_datetime' => $start->toDateTimeString(),
            'end_datetime' => $end->toDateTimeString(),
            'type' => $data['type'],
            'reason' => $data['reason'] ?? null,
            'status' => $approveNow ? self::STATUS_APPROVED : self::STATUS_PENDING,
            'requested_by' => $user->id,
            'reviewed_by' => $approveNow ? $user->id : null,
            'reviewed_at' => $approveNow ? now() : null,
        ]);
    }

    public function review(StaffTimeOff $leave, bool $approve, User $user, ?string $notes = null): StaffTimeOff
    {
        if ($leave->status !== self::STATUS_PENDING) {
            throw ValidationException::withMessages(['status' => 'This request has already been ' . $leave->status . '.']);
        }

        $leave->update([
            'status' => $approve ? self::STATUS_APPROVED : self::STATUS_REJECTED,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        return $leave;
    }

    public function cancel(StaffTimeOff $leave): StaffTimeOff
    {
        if (! in_array($leave->status, [self::STATUS_PENDING, self::STATUS_APPROVED], true)) {
            throw ValidationException::withMessages(['status' => 'This request can no longer be cancelled.']);
        }

        $leave->update(['status' => self::STATUS_CANCELLED]);

        return $leave;
    }

    /**
     * Bookings already made for the staff member during the leave, so the manager can move them.
     */
    public function clashingAppointments(StaffTimeOff $leave): int
    {
        return Appointment::withoutTenantScope()
            ->where('tenant_id', $leave->tenant_id)
            ->whereIn('status', Appointment::ACTIVE_STATUSES)
            ->where('starts_at', '<', $leave->end_datetime)
            ->where('ends_at', '>', $leave->start_datetime)
            ->where(fn ($query) => $query->where('staff_id', $leave->staff_id)
                ->orWhereIn('id', AppointmentService::withoutTenantScope()->where('staff_id', $leave->staff_id)->select('appointment_id')))
            ->count();
    }

    public function days(StaffTimeOff $leave): float
    {
        $hours = $leave->start_datetime->diffInHours($leave->end_datetime);

        return $hours < 12 ? 0.5 : (float) ($leave->start_datetime->startOfDay()->diffInDays($leave->end_datetime->startOfDay()) + 1);
    }
}
