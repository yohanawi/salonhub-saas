<?php

namespace App\Services\Staff;

use App\Models\Branch;
use App\Models\Staff;
use App\Models\StaffShift;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Week roster: each cell is either a rostered shift (an override for that date) or the staff member's
 * weekly schedule. Editing a cell always writes an override; clearing it falls back to the weekly schedule.
 */
class RosterService
{
    /**
     * @return array{days: Collection<int, CarbonImmutable>, staff: Collection<int, Staff>, cells: array<int, array<string, array>>}
     */
    public function week(Branch $branch, CarbonImmutable $weekStart): array
    {
        $days = collect(range(0, 6))->map(fn (int $offset) => $weekStart->addDays($offset));

        $staff = Staff::withoutTenantScope()
            ->with(['schedules' => fn ($query) => $query->where('branch_id', $branch->id)])
            ->where('tenant_id', $branch->tenant_id)
            ->whereIn('status', [Staff::STATUS_ACTIVE, Staff::STATUS_ON_LEAVE])
            ->whereHas('branches', fn ($query) => $query->where('branches.id', $branch->id))
            ->orderBy('first_name')
            ->get();

        $shifts = StaffShift::withoutTenantScope()
            ->whereIn('staff_id', $staff->pluck('id'))
            ->whereBetween('shift_date', [$weekStart->toDateString(), $weekStart->addDays(6)->toDateString()])
            ->get()
            ->groupBy('staff_id');

        $timeOff = \App\Models\StaffTimeOff::withoutTenantScope()
            ->whereIn('staff_id', $staff->pluck('id'))
            ->whereIn('status', ['approved', 'pending'])
            ->where('start_datetime', '<', $weekStart->addDays(7)->toDateTimeString())
            ->where('end_datetime', '>', $weekStart->toDateTimeString())
            ->get()
            ->groupBy('staff_id');

        $cells = [];

        foreach ($staff as $member) {
            foreach ($days as $day) {
                $date = $day->toDateString();
                $shift = ($shifts[$member->id] ?? collect())->first(fn (StaffShift $shift) => $shift->shift_date->toDateString() === $date);
                $weekly = $member->schedules->first(fn ($schedule) => (int) $schedule->day_of_week === $day->dayOfWeekIso && $schedule->is_working);
                $leave = ($timeOff[$member->id] ?? collect())->first(fn ($off) => $off->start_datetime->lt($day->endOfDay()) && $off->end_datetime->gt($day->startOfDay()));

                $cells[$member->id][$date] = match (true) {
                    $shift !== null && $shift->is_off => ['kind' => 'off', 'label' => 'Off', 'start' => null, 'end' => null],
                    $shift !== null && (int) $shift->branch_id !== (int) $branch->id => ['kind' => 'elsewhere', 'label' => 'At ' . ($shift->branch?->name ?? 'other branch'), 'start' => null, 'end' => null],
                    $shift !== null => ['kind' => 'shift', 'label' => $shift->label, 'start' => substr($shift->start_time, 0, 5), 'end' => substr($shift->end_time, 0, 5)],
                    $weekly !== null => ['kind' => 'weekly', 'label' => substr($weekly->getRawOriginal('start_time'), 0, 5) . '–' . substr($weekly->getRawOriginal('end_time'), 0, 5), 'start' => substr($weekly->getRawOriginal('start_time'), 0, 5), 'end' => substr($weekly->getRawOriginal('end_time'), 0, 5)],
                    default => ['kind' => 'none', 'label' => '', 'start' => null, 'end' => null],
                } + [
                    'leave' => $leave ? str($leave->type)->replace('_', ' ')->headline() . ($leave->status === 'pending' ? ' (pending)' : '') : null,
                ];
            }
        }

        return ['days' => $days, 'staff' => $staff, 'cells' => $cells];
    }

    public function set(Branch $branch, Staff $staff, CarbonImmutable $date, ?string $start, ?string $end, bool $off, User $user): StaffShift
    {
        $this->ensureStaffAtBranch($branch, $staff);

        if (! $off && (! $start || ! $end || $end <= $start)) {
            throw ValidationException::withMessages(['end_time' => 'The shift must end after it starts.']);
        }

        return StaffShift::withoutTenantScope()->updateOrCreate(
            ['staff_id' => $staff->id, 'shift_date' => $date->toDateString()],
            [
                'tenant_id' => $staff->tenant_id,
                'branch_id' => $branch->id,
                'start_time' => $off ? null : $start,
                'end_time' => $off ? null : $end,
                'is_off' => $off,
                'created_by' => $user->id,
            ]
        );
    }

    public function clear(Staff $staff, CarbonImmutable $date): void
    {
        StaffShift::withoutTenantScope()->where('staff_id', $staff->id)->whereDate('shift_date', $date->toDateString())->delete();
    }

    /**
     * Drag & drop: the shift in the source cell moves to the target cell; the source becomes a day off.
     */
    public function move(Branch $branch, Staff $fromStaff, CarbonImmutable $fromDate, Staff $toStaff, CarbonImmutable $toDate, User $user): void
    {
        $cell = $this->week($branch, $fromDate->startOfWeek())['cells'][$fromStaff->id][$fromDate->toDateString()] ?? null;

        if (! $cell || ! in_array($cell['kind'], ['shift', 'weekly'], true)) {
            throw ValidationException::withMessages(['shift' => 'There is no shift to move in that cell.']);
        }

        DB::transaction(function () use ($branch, $fromStaff, $fromDate, $toStaff, $toDate, $cell, $user) {
            $this->set($branch, $toStaff, $toDate, $cell['start'], $cell['end'], false, $user);

            if ($fromStaff->id !== $toStaff->id || ! $fromDate->isSameDay($toDate)) {
                $this->set($branch, $fromStaff, $fromDate, null, null, true, $user);
            }
        });
    }

    /**
     * Copies last week's effective roster (shifts and days off) onto this week as overrides.
     */
    public function copyPreviousWeek(Branch $branch, CarbonImmutable $weekStart, User $user): int
    {
        $previous = $this->week($branch, $weekStart->subWeek());
        $copied = 0;

        DB::transaction(function () use ($branch, $previous, $user, &$copied) {
            foreach ($previous['staff'] as $member) {
                foreach ($previous['days'] as $day) {
                    $cell = $previous['cells'][$member->id][$day->toDateString()];
                    $target = $day->addWeek();

                    if (in_array($cell['kind'], ['shift', 'weekly'], true)) {
                        $this->set($branch, $member, $target, $cell['start'], $cell['end'], false, $user);
                        $copied++;
                    } elseif ($cell['kind'] === 'off') {
                        $this->set($branch, $member, $target, null, null, true, $user);
                        $copied++;
                    }
                }
            }
        });

        return $copied;
    }

    private function ensureStaffAtBranch(Branch $branch, Staff $staff): void
    {
        if ((int) $staff->tenant_id !== (int) $branch->tenant_id || ! $staff->branches()->where('branches.id', $branch->id)->exists()) {
            throw ValidationException::withMessages(['staff_id' => "{$staff->full_name} is not assigned to {$branch->name}."]);
        }
    }
}
