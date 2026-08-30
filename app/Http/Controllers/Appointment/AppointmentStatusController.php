<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\Appointment\AppointmentStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AppointmentStatusController extends Controller
{
    public function confirm(Request $request, Appointment $appointment, AppointmentStatusService $statuses): RedirectResponse
    {
        $this->authorize('confirm', $appointment);
        $statuses->transition($appointment, Appointment::STATUS_CONFIRMED, $request->user());

        return back()->with('status', 'Appointment confirmed.');
    }

    public function checkIn(Request $request, Appointment $appointment, AppointmentStatusService $statuses): RedirectResponse
    {
        $this->authorize('checkIn', $appointment);
        $statuses->transition($appointment, Appointment::STATUS_CHECKED_IN, $request->user());

        return back()->with('status', 'Customer checked in.');
    }

    public function start(Request $request, Appointment $appointment, AppointmentStatusService $statuses): RedirectResponse
    {
        $this->authorize('start', $appointment);
        $statuses->transition($appointment, Appointment::STATUS_IN_PROGRESS, $request->user());

        return back()->with('status', 'Appointment started.');
    }

    public function complete(Request $request, Appointment $appointment, AppointmentStatusService $statuses): RedirectResponse
    {
        $this->authorize('complete', $appointment);
        $statuses->transition($appointment, Appointment::STATUS_COMPLETED, $request->user());

        return back()->with('status', 'Appointment completed.');
    }

    public function cancel(Request $request, Appointment $appointment, AppointmentStatusService $statuses): RedirectResponse
    {
        $this->authorize('cancel', $appointment);

        $data = $request->validate([
            'cancellation_reason' => ['nullable', 'string', 'max:3000'],
        ]);

        $statuses->transition($appointment, Appointment::STATUS_CANCELLED, $request->user(), $data['cancellation_reason'] ?? null);

        return back()->with('status', 'Appointment cancelled.');
    }

    public function noShow(Request $request, Appointment $appointment, AppointmentStatusService $statuses): RedirectResponse
    {
        $this->authorize('noShow', $appointment);
        $statuses->transition($appointment, Appointment::STATUS_NO_SHOW, $request->user());

        return back()->with('status', 'Appointment marked as no-show.');
    }
}
