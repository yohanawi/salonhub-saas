<?php

namespace App\Services\Appointment;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentBookingService
{
    public function __construct(
        private readonly AppointmentAvailabilityService $availability,
        private readonly AppointmentPricingService $pricing
    ) {
    }

    public function create(Tenant $tenant, array $data, User $user): Appointment
    {
        return DB::transaction(function () use ($tenant, $data, $user) {
            $prepared = $this->prepare($tenant, $data);
            $this->lockStaff($prepared['lines']);
            $this->validateAvailability($prepared);

            $appointment = Appointment::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $prepared['branch']->id,
                'customer_id' => $prepared['customer']->id,
                'staff_id' => $prepared['primary_staff_id'],
                'appointment_number' => null,
                'booking_source' => $data['booking_source'] ?? 'reception',
                'source' => $data['booking_source'] ?? 'reception',
                'status' => $data['status'] ?? Appointment::STATUS_PENDING,
                'starts_at' => $prepared['starts_at'],
                'ends_at' => $prepared['ends_at'],
                'subtotal' => $prepared['totals']['subtotal'],
                'discount_amount' => $prepared['totals']['discount_amount'],
                'tax_amount' => $prepared['totals']['tax_amount'],
                'total_amount' => $prepared['totals']['total_amount'],
                'customer_notes' => $data['customer_notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'notes' => $data['customer_notes'] ?? null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $appointment->update([
                'appointment_number' => $this->appointmentNumber($appointment),
            ]);

            $this->syncServices($appointment, $prepared['lines']);

            $appointment->statusHistory()->create([
                'tenant_id' => $tenant->id,
                'from_status' => null,
                'to_status' => $appointment->status,
                'changed_by' => $user->id,
                'notes' => 'Appointment created.',
            ]);

            return $appointment->fresh(['branch', 'customer', 'appointmentServices.service', 'appointmentServices.staff', 'statusHistory.changedBy']);
        });
    }

    public function update(Appointment $appointment, Tenant $tenant, array $data, User $user): Appointment
    {
        if (! in_array($appointment->status, [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED], true)) {
            throw ValidationException::withMessages([
                'appointment' => 'Only pending and confirmed appointments can be rescheduled.',
            ]);
        }

        return DB::transaction(function () use ($appointment, $tenant, $data, $user) {
            $prepared = $this->prepare($tenant, $data, $appointment);
            $this->lockStaff($prepared['lines']);
            $this->validateAvailability($prepared, $appointment);
            $fromStatus = $appointment->status;

            $appointment->update([
                'branch_id' => $prepared['branch']->id,
                'customer_id' => $prepared['customer']->id,
                'staff_id' => $prepared['primary_staff_id'],
                'booking_source' => $data['booking_source'] ?? $appointment->booking_source,
                'source' => $data['booking_source'] ?? $appointment->source,
                'status' => $data['status'] ?? $appointment->status,
                'starts_at' => $prepared['starts_at'],
                'ends_at' => $prepared['ends_at'],
                'subtotal' => $prepared['totals']['subtotal'],
                'discount_amount' => $prepared['totals']['discount_amount'],
                'tax_amount' => $prepared['totals']['tax_amount'],
                'total_amount' => $prepared['totals']['total_amount'],
                'customer_notes' => $data['customer_notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'notes' => $data['customer_notes'] ?? null,
                'updated_by' => $user->id,
            ]);

            $appointment->appointmentServices()->delete();
            $this->syncServices($appointment, $prepared['lines']);

            $appointment->statusHistory()->create([
                'tenant_id' => $tenant->id,
                'from_status' => $fromStatus,
                'to_status' => $appointment->status,
                'changed_by' => $user->id,
                'notes' => 'Appointment details updated.',
            ]);

            return $appointment->fresh(['branch', 'customer', 'appointmentServices.service', 'appointmentServices.staff', 'statusHistory.changedBy']);
        });
    }

    private function prepare(Tenant $tenant, array $data, ?Appointment $ignoreAppointment = null): array
    {
        $branch = Branch::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('status', Branch::STATUS_ACTIVE)
            ->findOrFail($data['branch_id']);

        $customer = Customer::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($data['customer_id']);

        if ($customer->status === Customer::STATUS_BLOCKED) {
            throw ValidationException::withMessages([
                'customer_id' => 'Blocked customers cannot be booked.',
            ]);
        }

        $cursor = CarbonImmutable::parse($data['starts_at']);
        $appointmentStart = $cursor;
        $lines = [];

        foreach (array_values($data['services'] ?? []) as $index => $line) {
            $service = Service::withoutTenantScope()
                ->with(['branches', 'staff'])
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->findOrFail($line['service_id']);

            if (! $service->isAvailableAt($branch)) {
                throw ValidationException::withMessages([
                    "services.{$index}.service_id" => "{$service->name} is not available at {$branch->name}.",
                ]);
            }

            $staff = Staff::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->findOrFail($line['staff_id']);

            $snapshot = $this->pricing->snapshot($service, $branch, $staff, (float) ($line['discount_amount'] ?? 0));
            $startsAt = $cursor;
            $endsAt = $startsAt->addMinutes($snapshot['duration_minutes']);

            $lines[] = [
                'index' => $index,
                'service' => $service,
                'staff' => $staff,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'snapshot' => $snapshot,
            ];

            $cursor = $endsAt;
        }

        if ($lines === []) {
            throw ValidationException::withMessages([
                'services' => 'Add at least one service to create an appointment.',
            ]);
        }

        return [
            'branch' => $branch,
            'customer' => $customer,
            'starts_at' => $appointmentStart,
            'ends_at' => $cursor,
            'primary_staff_id' => $lines[0]['staff']->id,
            'lines' => $lines,
            'totals' => $this->pricing->totals(
                collect($lines)->pluck('snapshot')->all(),
                (float) ($data['discount_amount'] ?? 0),
                (float) ($data['tax_amount'] ?? 0)
            ),
            'ignore_appointment' => $ignoreAppointment,
        ];
    }

    private function validateAvailability(array $prepared, ?Appointment $ignoreAppointment = null): void
    {
        foreach ($prepared['lines'] as $line) {
            if (! $this->availability->canBook(
                $line['staff'],
                $prepared['branch'],
                $line['service'],
                $line['starts_at'],
                $line['ends_at'],
                $ignoreAppointment
            )) {
                throw ValidationException::withMessages([
                    "services.{$line['index']}.staff_id" => "{$line['staff']->full_name} is not available for {$line['service']->name} at the selected time.",
                ]);
            }
        }
    }

    private function syncServices(Appointment $appointment, array $lines): void
    {
        foreach ($lines as $line) {
            $appointment->appointmentServices()->create([
                'tenant_id' => $appointment->tenant_id,
                'service_id' => $line['service']->id,
                'staff_id' => $line['staff']->id,
                'service_name' => $line['snapshot']['service_name'],
                'duration_minutes' => $line['snapshot']['duration_minutes'],
                'price' => $line['snapshot']['unit_price'],
                'unit_price' => $line['snapshot']['unit_price'],
                'discount_amount' => $line['snapshot']['discount_amount'],
                'total_price' => $line['snapshot']['total_price'],
                'starts_at' => $line['starts_at'],
                'ends_at' => $line['ends_at'],
                'status' => $appointment->status,
            ]);
        }
    }

    private function lockStaff(array $lines): void
    {
        Staff::withoutTenantScope()
            ->whereIn('id', collect($lines)->pluck('staff.id')->unique()->sort()->values())
            ->lockForUpdate()
            ->get();
    }

    private function appointmentNumber(Appointment $appointment): string
    {
        return 'APT-' . $appointment->starts_at->format('Ym') . '-' . str_pad((string) $appointment->id, 6, '0', STR_PAD_LEFT);
    }
}
