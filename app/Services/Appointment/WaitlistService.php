<?php

namespace App\Services\Appointment;

use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Services\StaffAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Customers waiting for a day that is full. When an appointment is cancelled, the entries its freed time suits
 * are offered to the front desk, oldest first, and can be booked into that time in one step.
 */
class WaitlistService
{
    public function __construct(
        private readonly AppointmentBookingService $bookings,
        private readonly StaffAvailabilityService $staffAvailability,
    ) {
    }

    public function add(Tenant $tenant, array $data, User $user): WaitlistEntry
    {
        return WaitlistEntry::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $data['branch_id'],
            'customer_id' => $data['customer_id'],
            'service_id' => $data['service_id'],
            'staff_id' => $data['staff_id'] ?? null,
            'preferred_date' => $data['preferred_date'],
            'preferred_from' => $data['preferred_from'] ?? null,
            'preferred_to' => $data['preferred_to'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => WaitlistEntry::STATUS_WAITING,
            'created_by' => $user->id,
        ]);
    }

    /**
     * Waiting entries the cancelled appointment's time suits, oldest first, each with the slot to offer them.
     *
     * @return Collection<int, array{entry: WaitlistEntry, starts_at: CarbonImmutable, staff: \App\Models\Staff}>
     */
    public function matchesFor(Appointment $appointment): Collection
    {
        if ($appointment->status !== Appointment::STATUS_CANCELLED || ! $appointment->starts_at) {
            return collect();
        }

        $lines = $appointment->appointmentServices()->with('staff')->get();

        $entries = WaitlistEntry::withoutTenantScope()
            ->with(['customer', 'service', 'staff'])
            ->where('tenant_id', $appointment->tenant_id)
            ->where('branch_id', $appointment->branch_id)
            ->where('status', WaitlistEntry::STATUS_WAITING)
            ->whereDate('preferred_date', $appointment->starts_at->toDateString())
            ->orderBy('created_at')
            ->get();

        return $entries
            ->map(function (WaitlistEntry $entry) use ($lines) {
                $line = $lines->first(fn (AppointmentService $line) => $this->suits($entry, $line));

                return $line ? [
                    'entry' => $entry,
                    'starts_at' => CarbonImmutable::parse($line->starts_at),
                    'staff' => $line->staff,
                ] : null;
            })
            ->filter()
            ->values();
    }

    public function book(WaitlistEntry $entry, CarbonImmutable $startsAt, int $staffId, User $user): Appointment
    {
        return DB::transaction(function () use ($entry, $startsAt, $staffId, $user) {
            $entry = WaitlistEntry::withoutTenantScope()->whereKey($entry->id)->lockForUpdate()->firstOrFail();

            if ($entry->status !== WaitlistEntry::STATUS_WAITING) {
                throw ValidationException::withMessages(['waitlist' => 'This customer is no longer waiting.']);
            }

            // The booking service checks the staff member's hours, skills and clashes like any other booking.
            $appointment = $this->bookings->create($entry->tenant, [
                'branch_id' => $entry->branch_id,
                'customer_id' => $entry->customer_id,
                'booking_source' => 'reception',
                'status' => Appointment::STATUS_CONFIRMED,
                'starts_at' => $startsAt->format('Y-m-d H:i:s'),
                'customer_notes' => $entry->notes,
                'internal_notes' => 'Booked from the waitlist.',
                'services' => [['service_id' => $entry->service_id, 'staff_id' => $staffId]],
            ], $user);

            $entry->update(['status' => WaitlistEntry::STATUS_BOOKED, 'appointment_id' => $appointment->id]);

            return $appointment;
        });
    }

    public function cancel(WaitlistEntry $entry): void
    {
        if ($entry->status !== WaitlistEntry::STATUS_WAITING) {
            throw ValidationException::withMessages(['waitlist' => 'Only waiting entries can be removed.']);
        }

        $entry->update(['status' => WaitlistEntry::STATUS_CANCELLED]);
    }

    /**
     * Entries for days that have passed. A day of slack covers salons in timezones ahead of the server.
     */
    public function expirePast(): int
    {
        return WaitlistEntry::withoutTenantScope()
            ->where('status', WaitlistEntry::STATUS_WAITING)
            ->whereDate('preferred_date', '<', now()->subDay()->toDateString())
            ->update(['status' => WaitlistEntry::STATUS_EXPIRED, 'updated_at' => now()]);
    }

    private function suits(WaitlistEntry $entry, AppointmentService $line): bool
    {
        if (! $line->staff || ($entry->staff_id && (int) $entry->staff_id !== (int) $line->staff_id)) {
            return false;
        }

        if (! $this->staffAvailability->canPerformService($line->staff, $entry->service_id)) {
            return false;
        }

        $start = CarbonImmutable::parse($line->starts_at)->format('H:i:s');

        return (! $entry->preferred_from || $start >= $this->time($entry->preferred_from))
            && (! $entry->preferred_to || $start < $this->time($entry->preferred_to));
    }

    private function time(string $time): string
    {
        return CarbonImmutable::parse($time)->format('H:i:s');
    }
}
