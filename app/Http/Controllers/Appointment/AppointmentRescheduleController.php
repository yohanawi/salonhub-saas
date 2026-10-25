<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\Appointment\AppointmentRescheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON endpoints behind the calendar timeline's drag (move) and resize handles.
 */
class AppointmentRescheduleController extends Controller
{
    public function move(Request $request, Appointment $appointment, AppointmentRescheduleService $reschedule): JsonResponse
    {
        $this->authorize('update', $appointment);

        $data = $request->validate([
            'starts_at' => ['required', 'date'],
            'from_staff_id' => ['nullable', 'integer'],
            'to_staff_id' => ['nullable', 'integer'],
        ]);

        $appointment = $reschedule->move(
            $appointment,
            CarbonImmutable::parse($data['starts_at']),
            isset($data['from_staff_id']) ? (int) $data['from_staff_id'] : null,
            isset($data['to_staff_id']) ? (int) $data['to_staff_id'] : null,
            $request->user()
        );

        return $this->result($appointment, 'Appointment moved.');
    }

    public function resize(Request $request, Appointment $appointment, AppointmentRescheduleService $reschedule): JsonResponse
    {
        $this->authorize('update', $appointment);

        $data = $request->validate([
            'staff_id' => ['required', 'integer'],
            'ends_at' => ['required', 'date'],
        ]);

        $appointment = $reschedule->resize($appointment, (int) $data['staff_id'], CarbonImmutable::parse($data['ends_at']), $request->user());

        return $this->result($appointment, 'Appointment length changed.');
    }

    private function result(Appointment $appointment, string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'appointment' => [
                'id' => $appointment->id,
                'starts_at' => $appointment->starts_at->format('Y-m-d H:i'),
                'ends_at' => $appointment->ends_at->format('Y-m-d H:i'),
            ],
        ]);
    }
}
