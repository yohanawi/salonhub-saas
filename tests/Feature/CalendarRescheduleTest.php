<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Appointment\AppointmentBookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Concerns\BuildsSalonFixtures;
use Tests\TestCase;

class CalendarRescheduleTest extends TestCase
{
    use BuildsSalonFixtures;
    use DatabaseTransactions;

    private Tenant $tenant;
    private User $owner;
    private Branch $branch;
    private Service $service;
    private Staff $stylist;
    private Staff $senior;
    private string $day;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();

        [$this->tenant, $this->owner, $this->branch] = $this->makeSalon();
        $this->service = $this->makeService($this->tenant, $this->branch, ['name' => 'Hair Cut', 'price' => 3000, 'default_price' => 3000, 'duration_minutes' => 60, 'default_duration_minutes' => 60]);
        $this->stylist = $this->bookableStaff();
        $this->senior = $this->bookableStaff(4500);
        $this->day = now('Asia/Colombo')->next(Carbon::MONDAY)->format('Y-m-d');
    }

    public function test_dragging_moves_the_appointment_to_a_new_time(): void
    {
        $appointment = $this->book('10:00');

        $this->actingAs($this->owner)
            ->patchJson(route('appointment-management.appointments.move', $appointment), [
                'starts_at' => "{$this->day} 11:30",
                'from_staff_id' => $this->stylist->id,
                'to_staff_id' => $this->stylist->id,
            ])
            ->assertOk()
            ->assertJsonPath('appointment.starts_at', "{$this->day} 11:30")
            ->assertJsonPath('appointment.ends_at', "{$this->day} 12:30");

        $line = $appointment->appointmentServices()->firstOrFail();
        $this->assertSame("{$this->day} 11:30", $line->starts_at->format('Y-m-d H:i'));
        $this->assertSame("{$this->day} 12:30", $line->ends_at->format('Y-m-d H:i'));
        $this->assertTrue($appointment->statusHistory()->where('notes', 'like', 'Rescheduled on the calendar.%')->exists());
    }

    public function test_dragging_to_another_column_reassigns_and_reprices_the_service(): void
    {
        $appointment = $this->book('10:00');

        $this->actingAs($this->owner)
            ->patchJson(route('appointment-management.appointments.move', $appointment), [
                'starts_at' => "{$this->day} 10:00",
                'from_staff_id' => $this->stylist->id,
                'to_staff_id' => $this->senior->id,
            ])
            ->assertOk();

        $appointment->refresh();
        $line = $appointment->appointmentServices()->firstOrFail();
        $this->assertSame($this->senior->id, (int) $line->staff_id);
        $this->assertSame($this->senior->id, (int) $appointment->staff_id);
        $this->assertEquals('4500.00', $line->unit_price);
        $this->assertEquals('4500.00', $appointment->total_amount);
    }

    public function test_moves_into_a_clash_outside_working_hours_or_into_the_past_are_refused(): void
    {
        $appointment = $this->book('10:00');
        $this->book('13:00');

        $move = fn (string $startsAt) => $this->actingAs($this->owner)
            ->patchJson(route('appointment-management.appointments.move', $appointment), [
                'starts_at' => $startsAt,
                'from_staff_id' => $this->stylist->id,
                'to_staff_id' => $this->stylist->id,
            ]);

        $move("{$this->day} 12:30")->assertStatus(422)->assertJsonValidationErrors('staff_id');
        $move("{$this->day} 16:30")->assertStatus(422)->assertJsonValidationErrors('staff_id');
        $move(now('Asia/Colombo')->subDay()->format('Y-m-d') . ' 10:00')
            ->assertStatus(422)
            ->assertJsonPath('errors.starts_at.0', 'The appointment time is in the past.');

        $this->assertSame("{$this->day} 10:00", $appointment->fresh()->starts_at->format('Y-m-d H:i'));
    }

    public function test_dragging_the_bottom_edge_changes_the_length(): void
    {
        $appointment = $this->book('10:00');

        $this->actingAs($this->owner)
            ->patchJson(route('appointment-management.appointments.resize', $appointment), [
                'staff_id' => $this->stylist->id,
                'ends_at' => "{$this->day} 11:30",
            ])
            ->assertOk()
            ->assertJsonPath('appointment.ends_at', "{$this->day} 11:30");

        $this->assertSame(90, $appointment->appointmentServices()->firstOrFail()->duration_minutes);

        $this->actingAs($this->owner)
            ->patchJson(route('appointment-management.appointments.resize', $appointment), [
                'staff_id' => $this->stylist->id,
                'ends_at' => "{$this->day} 10:00",
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('ends_at');

        // Stretching into the next booking is refused.
        $this->book('12:00');
        $this->actingAs($this->owner)
            ->patchJson(route('appointment-management.appointments.resize', $appointment), [
                'staff_id' => $this->stylist->id,
                'ends_at' => "{$this->day} 12:30",
            ])
            ->assertStatus(422);
    }

    public function test_only_pending_and_confirmed_appointments_can_be_dragged(): void
    {
        $appointment = $this->book('10:00');
        $appointment->forceFill(['status' => Appointment::STATUS_CHECKED_IN])->saveQuietly();

        $this->actingAs($this->owner)
            ->patchJson(route('appointment-management.appointments.move', $appointment), [
                'starts_at' => "{$this->day} 11:00",
            ])
            ->assertForbidden();
    }

    public function test_calendar_shows_the_timeline_with_drag_handles(): void
    {
        $appointment = $this->book('10:00');

        $this->actingAs($this->owner)
            ->get(route('appointment-management.appointments.calendar', ['date' => $this->day]))
            ->assertOk()
            ->assertSee('id="appointment-timeline"', false)
            ->assertSee(route('appointment-management.appointments.move', $appointment), false)
            ->assertSee('tl-resize', false);

        $this->actingAs($this->owner)
            ->get(route('appointment-management.appointments.calendar', ['date' => $this->day, 'view' => 'cards']))
            ->assertOk()
            ->assertSee('Staff Schedule')
            ->assertDontSee('id="appointment-timeline"', false);
    }

    private function book(string $time): Appointment
    {
        return app(AppointmentBookingService::class)->create($this->tenant, [
            'branch_id' => $this->branch->id,
            'customer_id' => $this->makeCustomer($this->tenant, $this->branch)->id,
            'booking_source' => 'reception',
            'starts_at' => "{$this->day} {$time}",
            'services' => [['service_id' => $this->service->id, 'staff_id' => $this->stylist->id]],
        ], $this->owner);
    }

    private function bookableStaff(?float $customPrice = null): Staff
    {
        $staff = $this->makeStaff($this->tenant, $this->branch);
        $staff->services()->attach($this->service->id, [
            'tenant_id' => $this->tenant->id,
            'custom_price' => $customPrice,
            'status' => 'active',
        ]);

        foreach (range(1, 7) as $day) {
            $staff->schedules()->create([
                'tenant_id' => $this->tenant->id,
                'branch_id' => $this->branch->id,
                'day_of_week' => $day,
                'is_working' => true,
                'start_time' => '09:00',
                'end_time' => '17:00',
            ]);
        }

        return $staff;
    }
}
