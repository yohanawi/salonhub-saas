<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendance;
use App\Models\StaffTimeOff;
use App\Services\Staff\AttendanceService;
use App\Services\Staff\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * A staff member's own clock-in/out and leave requests (My Profile → Attendance / Leave).
 */
class MyWorkController extends Controller
{
    public function attendance(Request $request, AttendanceService $attendance): View
    {
        $staff = $request->user()->staffProfile;

        return view('pages/apps.profile.attendance', [
            'staff' => $staff,
            'today' => $staff ? $attendance->today($staff) : null,
            'now' => $staff ? $attendance->now($staff) : now(),
            'history' => $staff
                ? StaffAttendance::withoutTenantScope()->with('branch')->where('staff_id', $staff->id)->latest('work_date')->limit(31)->get()
                : collect(),
        ]);
    }

    public function clockIn(Request $request, AttendanceService $attendance): RedirectResponse
    {
        $staff = $request->user()->staffProfile;
        abort_unless($staff, 403);

        $record = $attendance->clockIn($staff, null, $request->user());

        return back()->with('status', 'Clocked in at ' . $record->clock_in_at->format('H:i') . ($record->status === StaffAttendance::STATUS_LATE ? " ({$record->late_minutes} min late)." : '.'));
    }

    public function clockOut(Request $request, AttendanceService $attendance): RedirectResponse
    {
        $staff = $request->user()->staffProfile;
        abort_unless($staff, 403);

        $data = $request->validate(['break_minutes' => ['nullable', 'integer', 'min:0', 'max:600']]);
        $record = $attendance->clockOut($staff, $request->user(), (int) ($data['break_minutes'] ?? 0));

        return back()->with('status', 'Clocked out. You worked ' . $record->worked_label . ' today.');
    }

    public function leave(Request $request, LeaveService $leaves): View
    {
        $staff = $request->user()->staffProfile;
        $requests = $staff
            ? StaffTimeOff::withoutTenantScope()->where('staff_id', $staff->id)->latest('start_datetime')->limit(30)->get()
            : collect();

        return view('pages/apps.profile.leave', [
            'staff' => $staff,
            'requests' => $requests,
            'days' => $requests->mapWithKeys(fn (StaffTimeOff $leave) => [$leave->id => $leaves->days($leave)]),
            'types' => StaffTimeOff::TYPES,
        ]);
    }

    public function requestLeave(Request $request, LeaveService $leaves): RedirectResponse
    {
        $staff = $request->user()->staffProfile;
        abort_unless($staff, 403);

        $data = $request->validate([
            'type' => ['required', Rule::in(StaffTimeOff::TYPES)],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'half_day' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $leaves->request($staff, $data, $request->user());

        return back()->with('status', 'Leave request sent for approval.');
    }

    public function cancelLeave(Request $request, StaffTimeOff $leave, LeaveService $leaves): RedirectResponse
    {
        $staff = $request->user()->staffProfile;
        abort_unless($staff && (int) $leave->staff_id === (int) $staff->id, 403);
        // Staff may withdraw a request, or approved leave that has not started yet.
        abort_if($leave->status === LeaveService::STATUS_APPROVED && $leave->start_datetime->isPast(), 403);

        $leaves->cancel($leave);

        return back()->with('status', 'Leave request cancelled.');
    }
}
