<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentDeposit;
use App\Services\Appointment\AppointmentStatusService;
use App\Services\Appointment\WaitlistService;
use App\Services\Inventory\ServiceConsumptionService;
use App\Services\Staff\FeedbackService;
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

    public function complete(Request $request, Appointment $appointment, AppointmentStatusService $statuses, ServiceConsumptionService $consumption): RedirectResponse
    {
        $this->authorize('complete', $appointment);
        $appointment = $statuses->transition($appointment, Appointment::STATUS_COMPLETED, $request->user());

        $shortages = $consumption->consumeForAppointment($appointment, $request->user());
        app(FeedbackService::class)->requestFor($appointment, $request->user());

        $response = back()->with('status', 'Appointment completed.');

        return $shortages
            ? $response->with('warning', 'Not enough stock to deduct: ' . implode(', ', $shortages) . '. Adjust stock manually once it is counted.')
            : $response;
    }

    /**
     * Deducts consumables that were short when the appointment was completed, once the branch has restocked.
     */
    public function consumeStock(Request $request, Appointment $appointment, ServiceConsumptionService $consumption): RedirectResponse
    {
        $this->authorize('complete', $appointment);
        abort_unless($appointment->status === Appointment::STATUS_COMPLETED, 422, 'Only completed appointments use up stock.');

        $shortages = $consumption->consumeForAppointment($appointment, $request->user());

        return $shortages
            ? back()->with('warning', 'Still not enough stock to deduct: ' . implode(', ', $shortages) . '.')
            : back()->with('status', 'Service products deducted from stock.');
    }

    public function cancel(Request $request, Appointment $appointment, AppointmentStatusService $statuses): RedirectResponse
    {
        $this->authorize('cancel', $appointment);

        $data = $request->validate([
            'cancellation_reason' => ['nullable', 'string', 'max:3000'],
        ]);

        $appointment = $statuses->transition($appointment, Appointment::STATUS_CANCELLED, $request->user(), $data['cancellation_reason'] ?? null);

        $message = $appointment->cancelled_late ? 'Appointment cancelled late (inside the cut-off).' : 'Appointment cancelled.';
        $message .= $this->forfeitedNote($appointment);

        $waiting = app(WaitlistService::class)->matchesFor($appointment)->count();

        if ($waiting > 0) {
            $message .= " {$waiting} " . str('customer')->plural($waiting) . ' on the waitlist suit this slot.';
        }

        return back()->with('status', $message);
    }

    public function noShow(Request $request, Appointment $appointment, AppointmentStatusService $statuses): RedirectResponse
    {
        $this->authorize('noShow', $appointment);
        $appointment = $statuses->transition($appointment, Appointment::STATUS_NO_SHOW, $request->user());

        return back()->with('status', 'Appointment marked as no-show.' . $this->forfeitedNote($appointment));
    }

    private function forfeitedNote(Appointment $appointment): string
    {
        $kept = (float) $appointment->deposits()->where('status', AppointmentDeposit::STATUS_FORFEITED)->get()->sum(fn ($deposit) => $deposit->remainingAmount());

        return $kept > 0 ? ' The deposit of ' . number_format($kept, 2) . ' was kept.' : '';
    }
}
