<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffTimeOff;
use App\Services\Staff\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Managers: review leave requests and record leave on someone's behalf.
 */
class LeaveController extends Controller
{
    public function index(Request $request, LeaveService $leaves): View
    {
        $this->ensureAccess($request);
        $tenantId = $request->user()->tenant_id;
        $status = $request->query('status', 'pending');

        $requests = StaffTimeOff::withoutTenantScope()
            ->with('staff')
            ->where('tenant_id', $tenantId)
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest('start_datetime')
            ->paginate(20)
            ->withQueryString();

        return view('pages/apps.staff-management.leave', [
            'requests' => $requests,
            'status' => $status,
            'clashes' => $requests->getCollection()->mapWithKeys(fn (StaffTimeOff $leave) => [$leave->id => $leave->status === LeaveService::STATUS_PENDING ? $leaves->clashingAppointments($leave) : 0]),
            'days' => $requests->getCollection()->mapWithKeys(fn (StaffTimeOff $leave) => [$leave->id => $leaves->days($leave)]),
            'pendingCount' => StaffTimeOff::withoutTenantScope()->where('tenant_id', $tenantId)->where('status', LeaveService::STATUS_PENDING)->count(),
            'staffMembers' => Staff::withoutTenantScope()->where('tenant_id', $tenantId)->whereIn('status', [Staff::STATUS_ACTIVE, Staff::STATUS_ON_LEAVE])->orderBy('first_name')->get(),
            'types' => StaffTimeOff::TYPES,
        ]);
    }

    public function store(Request $request, LeaveService $leaves): RedirectResponse
    {
        $this->ensureAccess($request);

        $data = $request->validate([
            'staff_id' => ['required', 'integer'],
            'type' => ['required', Rule::in(StaffTimeOff::TYPES)],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'half_day' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $staff = Staff::withoutTenantScope()->where('tenant_id', $request->user()->tenant_id)->findOrFail($data['staff_id']);
        $leaves->request($staff, $data, $request->user(), approveNow: true);

        return back()->with('status', "Leave recorded for {$staff->full_name}.");
    }

    public function review(Request $request, StaffTimeOff $leave, LeaveService $leaves): RedirectResponse
    {
        $this->ensureAccess($request);
        abort_unless((int) $leave->tenant_id === (int) $request->user()->tenant_id, 404);

        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $leaves->review($leave, $data['decision'] === 'approve', $request->user(), $data['review_notes'] ?? null);

        return back()->with('status', 'Leave request ' . ($data['decision'] === 'approve' ? 'approved.' : 'rejected.'));
    }

    private function ensureAccess(Request $request): void
    {
        abort_unless($request->user()->tenant_id && $request->user()->can('leave.approve'), 403);
    }
}
