<?php

namespace App\Services\Appointment;

use App\Models\Appointment;
use App\Models\Staff;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StaffAgendaService
{
    /**
     * Appointments for the staff member's working day, split into today and tomorrow.
     *
     * @return array{today: Collection, tomorrow: Collection, todayDate: CarbonImmutable, tomorrowDate: CarbonImmutable}
     */
    public function agenda(Staff $staff): array
    {
        $today = $this->now($staff)->startOfDay();
        $tomorrow = $today->addDay();

        $appointments = $this->query($staff)
            ->whereBetween('starts_at', [$today, $tomorrow->endOfDay()])
            ->with([
                'branch:id,name',
                'customer:id,first_name,last_name,phone',
                'appointmentServices' => fn ($query) => $query->orderBy('starts_at'),
                'appointmentServices.staff:id,first_name,last_name',
            ])
            ->orderBy('starts_at')
            ->get();

        return [
            'today' => $appointments->filter(fn (Appointment $appointment) => $appointment->starts_at->lt($tomorrow))->values(),
            'tomorrow' => $appointments->filter(fn (Appointment $appointment) => $appointment->starts_at->gte($tomorrow))->values(),
            'todayDate' => $today,
            'tomorrowDate' => $tomorrow,
        ];
    }

    public function todayCount(Staff $staff): int
    {
        $today = $this->now($staff)->startOfDay();

        return $this->query($staff)
            ->whereBetween('starts_at', [$today, $today->endOfDay()])
            ->whereIn('status', Appointment::ACTIVE_STATUSES)
            ->count();
    }

    /**
     * Appointments where the staff member is the lead stylist or performs at least one service.
     */
    private function query(Staff $staff): Builder
    {
        return Appointment::query()
            ->where('tenant_id', $staff->tenant_id)
            ->where(function (Builder $query) use ($staff) {
                $query->where('staff_id', $staff->id)
                    ->orWhereHas('appointmentServices', fn (Builder $services) => $services->where('staff_id', $staff->id));
            });
    }

    private function now(Staff $staff): CarbonImmutable
    {
        return CarbonImmutable::now($staff->tenant?->timezone ?: config('app.timezone'));
    }
}
