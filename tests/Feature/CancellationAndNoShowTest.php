<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentDeposit;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Settings\SettingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Concerns\BuildsSalonFixtures;
use Tests\TestCase;

class CancellationAndNoShowTest extends TestCase
{
    use BuildsSalonFixtures;
    use DatabaseTransactions;

    private Tenant $tenant;
    private User $owner;
    private Branch $branch;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();

        [$this->tenant, $this->owner, $this->branch] = $this->makeSalon();
        $this->customer = $this->makeCustomer($this->tenant, $this->branch);
    }

    public function test_cancelling_inside_the_cutoff_is_late_and_keeps_the_deposit(): void
    {
        $appointment = $this->appointment(startsInMinutes: 180); // cut-off is 12 hours by default
        $deposit = $this->deposit($appointment, 1000);

        $this->actingAs($this->owner)
            ->get(route('appointment-management.appointments.show', $appointment))
            ->assertSee('This is a late cancellation (less than 12 hours before the appointment). The deposit of 1,000.00 will be kept.');

        $this->actingAs($this->owner)
            ->post(route('appointment-management.appointments.cancel', $appointment))
            ->assertSessionHas('status', 'Appointment cancelled late (inside the cut-off). The deposit of 1,000.00 was kept.');

        $this->assertTrue($appointment->fresh()->cancelled_late);
        $deposit->refresh();
        $this->assertSame(AppointmentDeposit::STATUS_FORFEITED, $deposit->status);
        $this->assertSame($this->owner->id, (int) $deposit->resolved_by);
    }

    public function test_cancelling_early_leaves_the_deposit_held_for_a_refund(): void
    {
        $appointment = $this->appointment(startsInMinutes: 2 * 24 * 60);
        $deposit = $this->deposit($appointment, 1000);

        $this->actingAs($this->owner)
            ->post(route('appointment-management.appointments.cancel', $appointment))
            ->assertSessionHas('status', 'Appointment cancelled.');

        $this->assertFalse($appointment->fresh()->cancelled_late);
        $this->assertSame(AppointmentDeposit::STATUS_HELD, $deposit->fresh()->status);
    }

    public function test_salon_can_choose_to_return_deposits_on_late_cancellation(): void
    {
        app(SettingService::class)->setMany($this->tenant, null, ['booking.forfeit_deposit_on_late_cancellation' => false], $this->owner);
        $appointment = $this->appointment(startsInMinutes: 60);
        $deposit = $this->deposit($appointment, 500);

        $this->actingAs($this->owner)->post(route('appointment-management.appointments.cancel', $appointment));

        $this->assertTrue($appointment->fresh()->cancelled_late);
        $this->assertSame(AppointmentDeposit::STATUS_HELD, $deposit->fresh()->status);
    }

    public function test_marking_a_no_show_keeps_the_deposit(): void
    {
        $appointment = $this->appointment(startsInMinutes: -30, status: Appointment::STATUS_CONFIRMED);
        $deposit = $this->deposit($appointment, 750);

        $this->actingAs($this->owner)
            ->post(route('appointment-management.appointments.no-show', $appointment))
            ->assertSessionHas('status', 'Appointment marked as no-show. The deposit of 750.00 was kept.');

        $this->assertSame(AppointmentDeposit::STATUS_FORFEITED, $deposit->fresh()->status);
    }

    public function test_job_marks_overdue_bookings_as_no_shows_when_the_salon_turns_it_on(): void
    {
        $overdue = $this->appointment(startsInMinutes: -30, status: Appointment::STATUS_CONFIRMED);
        $unconfirmed = $this->appointment(startsInMinutes: -45);
        $justLate = $this->appointment(startsInMinutes: -10, status: Appointment::STATUS_CONFIRMED);
        $checkedIn = $this->appointment(startsInMinutes: -60, status: Appointment::STATUS_CHECKED_IN);
        $old = $this->appointment(startsInMinutes: -5 * 24 * 60, status: Appointment::STATUS_CONFIRMED);
        $deposit = $this->deposit($overdue, 400);

        // Off by default: nothing changes.
        $this->artisan('appointments:mark-no-shows')->assertSuccessful();
        $this->assertSame(Appointment::STATUS_CONFIRMED, $overdue->fresh()->status);

        app(SettingService::class)->setMany($this->tenant, null, ['appointment_status.auto_mark_no_show' => true], $this->owner);
        $this->artisan('appointments:mark-no-shows', ['--tenant_id' => $this->tenant->id])->assertSuccessful();

        $this->assertSame(Appointment::STATUS_NO_SHOW, $overdue->fresh()->status);
        $this->assertSame(Appointment::STATUS_NO_SHOW, $unconfirmed->fresh()->status);
        $this->assertSame(Appointment::STATUS_CONFIRMED, $justLate->fresh()->status); // inside the 20-minute grace
        $this->assertSame(Appointment::STATUS_CHECKED_IN, $checkedIn->fresh()->status);
        $this->assertSame(Appointment::STATUS_CONFIRMED, $old->fresh()->status); // older history is left alone

        $history = $overdue->statusHistory()->latest('id')->firstOrFail();
        $this->assertNull($history->changed_by);
        $this->assertStringContainsString('automatically', $history->notes);
        $this->assertSame(AppointmentDeposit::STATUS_FORFEITED, $deposit->fresh()->status);

        // The appointment page still renders with a system-made change in its history.
        $this->actingAs($this->owner)->get(route('appointment-management.appointments.show', $overdue))->assertOk();
    }

    private function appointment(int $startsInMinutes, string $status = Appointment::STATUS_PENDING): Appointment
    {
        // Appointment times are stored as the branch's local wall-clock time.
        $startsAt = now('Asia/Colombo')->addMinutes($startsInMinutes)->format('Y-m-d H:i:s');

        return Appointment::withoutTenantScope()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'appointment_number' => 'APT-' . fake()->unique()->numberBetween(100000, 999999),
            'status' => $status,
            'starts_at' => $startsAt,
            'ends_at' => \Carbon\Carbon::parse($startsAt)->addHour(),
            'created_by' => $this->owner->id,
        ]);
    }

    private function deposit(Appointment $appointment, float $amount): AppointmentDeposit
    {
        return AppointmentDeposit::withoutTenantScope()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'appointment_id' => $appointment->id,
            'customer_id' => $this->customer->id,
            'amount' => $amount,
            'status' => AppointmentDeposit::STATUS_HELD,
            'received_at' => now(),
        ]);
    }
}
