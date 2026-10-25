<?php

namespace App\Services\Staff;

use App\Models\Branch;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\StaffTimeOff;
use App\Models\User;
use App\Services\StaffAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Clock-in / clock-out. One record per staff member per working day; lateness is measured against the
 * rostered shift (or weekly schedule) with a short grace period.
 */
class AttendanceService
{
    public const GRACE_MINUTES = 5;

    public function __construct(private readonly StaffAvailabilityService $availability)
    {
    }

    public function now(Staff $staff): CarbonImmutable
    {
        return CarbonImmutable::now($staff->tenant?->timezone ?: config('app.timezone'));
    }

    public function today(Staff $staff): ?StaffAttendance
    {
        return StaffAttendance::withoutTenantScope()
            ->where('staff_id', $staff->id)
            ->whereDate('work_date', $this->now($staff)->toDateString())
            ->first();
    }

    public function clockIn(Staff $staff, ?Branch $branch, User $user): StaffAttendance
    {
        $now = $this->now($staff);
        $existing = $this->today($staff);

        if ($existing?->clock_in_at) {
            throw ValidationException::withMessages(['attendance' => 'You have already clocked in today at ' . $existing->clock_in_at->format('H:i') . '.']);
        }

        $branch ??= $staff->primaryBranch() ?? $staff->branches()->first();
        $window = $branch ? $this->availability->workingWindow($staff, $branch->id, $now) : null;
        $late = $window ? max(0, (int) floor(CarbonImmutable::parse($now->toDateString() . ' ' . $window[0], $now->getTimezone())->diffInMinutes($now, false))) : 0;

        return StaffAttendance::withoutTenantScope()->updateOrCreate(
            ['staff_id' => $staff->id, 'work_date' => $now->toDateString()],
            [
                'tenant_id' => $staff->tenant_id,
                'branch_id' => $branch?->id,
                'clock_in_at' => $now->toDateTimeString(),
                'scheduled_start' => $window[0] ?? null,
                'scheduled_end' => $window[1] ?? null,
                'late_minutes' => $late,
                'status' => $late > self::GRACE_MINUTES ? StaffAttendance::STATUS_LATE : StaffAttendance::STATUS_PRESENT,
                'source' => (int) $staff->user_id === (int) $user->id ? 'self' : 'manager',
            ]
        );
    }

    public function clockOut(Staff $staff, User $user, int $breakMinutes = 0): StaffAttendance
    {
        $attendance = $this->today($staff);

        if (! $attendance?->isOpen()) {
            throw ValidationException::withMessages(['attendance' => 'Clock in first.']);
        }

        $now = $this->now($staff);
        $clockIn = CarbonImmutable::parse($attendance->clock_in_at->format('Y-m-d H:i:s'), $now->getTimezone());

        $attendance->update([
            'clock_out_at' => $now->toDateTimeString(),
            'break_minutes' => max(0, $breakMinutes),
            'worked_minutes' => max(0, (int) $clockIn->diffInMinutes($now) - max(0, $breakMinutes)),
        ]);

        return $attendance;
    }

    /**
     * Manager entry or correction for any day (also used to mark someone absent or on leave).
     */
    public function record(Staff $staff, CarbonImmutable $date, array $data, User $user): StaffAttendance
    {
        $status = $data['status'];
        $in = ! empty($data['clock_in']) ? CarbonImmutable::parse($date->toDateString() . ' ' . $data['clock_in']) : null;
        $out = ! empty($data['clock_out']) ? CarbonImmutable::parse($date->toDateString() . ' ' . $data['clock_out']) : null;

        if (in_array($status, [StaffAttendance::STATUS_PRESENT, StaffAttendance::STATUS_LATE], true) && ! $in) {
            throw ValidationException::withMessages(['clock_in' => 'Enter the clock-in time.']);
        }

        if ($in && $out && $out->lessThanOrEqualTo($in)) {
            throw ValidationException::withMessages(['clock_out' => 'Clock-out must be after clock-in.']);
        }

        $branchId = $data['branch_id'] ?? $staff->primaryBranch()?->id;
        $window = $branchId ? $this->availability->workingWindow($staff, (int) $branchId, $date) : null;
        $late = $in && $window ? max(0, (int) floor(CarbonImmutable::parse($date->toDateString() . ' ' . $window[0])->diffInMinutes($in, false))) : 0;
        $break = (int) ($data['break_minutes'] ?? 0);

        if ($in && in_array($status, [StaffAttendance::STATUS_PRESENT, StaffAttendance::STATUS_LATE], true)) {
            $status = $late > self::GRACE_MINUTES ? StaffAttendance::STATUS_LATE : StaffAttendance::STATUS_PRESENT;
        }

        return StaffAttendance::withoutTenantScope()->updateOrCreate(
            ['staff_id' => $staff->id, 'work_date' => $date->toDateString()],
            [
                'tenant_id' => $staff->tenant_id,
                'branch_id' => $branchId,
                'clock_in_at' => $in?->toDateTimeString(),
                'clock_out_at' => $out?->toDateTimeString(),
                'break_minutes' => $break,
                'worked_minutes' => $in && $out ? max(0, (int) $in->diffInMinutes($out) - $break) : null,
                'scheduled_start' => $window[0] ?? null,
                'scheduled_end' => $window[1] ?? null,
                'late_minutes' => $in ? $late : 0,
                'status' => $status,
                'source' => 'manual',
                'notes' => $data['notes'] ?? null,
                'edited_by' => $user->id,
            ]
        );
    }

    /**
     * Everyone expected at a branch on a date, with their attendance (or what is missing).
     */
    public function board(Branch $branch, CarbonImmutable $date): Collection
    {
        $staff = Staff::withoutTenantScope()
            ->where('tenant_id', $branch->tenant_id)
            ->whereIn('status', [Staff::STATUS_ACTIVE, Staff::STATUS_ON_LEAVE])
            ->whereHas('branches', fn ($query) => $query->where('branches.id', $branch->id))
            ->orderBy('first_name')
            ->get();

        $records = StaffAttendance::withoutTenantScope()
            ->whereIn('staff_id', $staff->pluck('id'))
            ->whereDate('work_date', $date->toDateString())
            ->get()
            ->keyBy('staff_id');

        $leave = StaffTimeOff::withoutTenantScope()
            ->whereIn('staff_id', $staff->pluck('id'))
            ->where('status', 'approved')
            ->where('start_datetime', '<', $date->endOfDay()->toDateTimeString())
            ->where('end_datetime', '>', $date->startOfDay()->toDateTimeString())
            ->get()
            ->keyBy('staff_id');

        return $staff->map(fn (Staff $member) => [
            'staff' => $member,
            'window' => $this->availability->workingWindow($member, $branch->id, $date),
            'record' => $records->get($member->id),
            'leave' => $leave->get($member->id),
        ]);
    }

    /**
     * @return Collection<int, array{staff: Staff, days: int, late: int, absent: int, leave: int, minutes: int}>
     */
    public function monthlySummary(Branch $branch, CarbonImmutable $month): Collection
    {
        return StaffAttendance::withoutTenantScope()
            ->with('staff')
            ->where('tenant_id', $branch->tenant_id)
            ->where('branch_id', $branch->id)
            ->whereBetween('work_date', [$month->startOfMonth()->toDateString(), $month->endOfMonth()->toDateString()])
            ->get()
            ->groupBy('staff_id')
            ->map(fn (Collection $rows) => [
                'staff' => $rows->first()->staff,
                'days' => $rows->whereIn('status', [StaffAttendance::STATUS_PRESENT, StaffAttendance::STATUS_LATE])->count(),
                'late' => $rows->where('status', StaffAttendance::STATUS_LATE)->count(),
                'late_minutes' => (int) $rows->sum('late_minutes'),
                'absent' => $rows->where('status', StaffAttendance::STATUS_ABSENT)->count(),
                'leave' => $rows->where('status', StaffAttendance::STATUS_LEAVE)->count(),
                'minutes' => (int) $rows->sum('worked_minutes'),
            ])
            ->sortBy(fn ($row) => $row['staff']?->full_name)
            ->values();
    }
}
