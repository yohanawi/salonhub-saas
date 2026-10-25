<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Services\Appointment\AppointmentBookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Concerns\BuildsSalonFixtures;
use Tests\TestCase;

class WaitlistTest extends TestCase
{
    use BuildsSalonFixtures;
    use DatabaseTransactions;

    private Tenant $tenant;
    private User $owner;
    private Branch $branch;
    private Service $service;
    private Staff $stylist;
    private string $day;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();

        [$this->tenant, $this->owner, $this->branch] = $this->makeSalon();
        $this->service = $this->makeService($this->tenant, $this->branch, ['name' => 'Hair Cut']);
        $this->stylist = $this->makeStaff($this->tenant, $this->branch);
        $this->stylist->services()->attach($this->service->id, ['tenant_id' => $this->tenant->id, 'status' => 'active']);

        foreach (range(1, 7) as $day) {
            $this->stylist->schedules()->create([
                'tenant_id' => $this->tenant->id, 'branch_id' => $this->branch->id, 'day_of_week' => $day,
                'is_working' => true, 'start_time' => '09:00', 'end_time' => '17:00',
            ]);
        }

        $this->day = now('Asia/Colombo')->next(Carbon::MONDAY)->format('Y-m-d');
    }

    public function test_receptionist_adds_a_customer_to_the_waitlist(): void
    {
        $customer = $this->makeCustomer($this->tenant, $this->branch);

        $this->actingAs($this->owner)
            ->post(route('appointment-management.waitlist.store'), [
                'branch_id' => $this->branch->id,
                'customer_id' => $customer->id,
                'service_id' => $this->service->id,
                'preferred_date' => $this->day,
                'preferred_from' => '09:00',
                'preferred_to' => '12:00',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('waitlist_entries', [
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'status' => WaitlistEntry::STATUS_WAITING,
        ]);

        $this->actingAs($this->owner)
            ->get(route('appointment-management.waitlist.index'))
            ->assertOk()
            ->assertSee($customer->full_name);
    }

    public function test_cancelled_slot_is_offered_to_matching_waiting_customers_and_booked_in_one_step(): void
    {
        $appointment = $this->book('10:00');
        $first = $this->wait('09:00', '12:00');
        $second = $this->wait('09:00', '12:00');
        $this->wait('14:00', '16:00'); // wants the afternoon: does not suit a 10:00 slot

        $this->actingAs($this->owner)
            ->post(route('appointment-management.appointments.cancel', $appointment))
            ->assertSessionHas('status', 'Appointment cancelled. 2 customers on the waitlist suit this slot.');

        $this->actingAs($this->owner)
            ->get(route('appointment-management.appointments.show', $appointment))
            ->assertOk()
            ->assertSee('Fill this slot from the waitlist')
            ->assertSeeInOrder([$first->customer->full_name, $second->customer->full_name]);

        $this->actingAs($this->owner)
            ->post(route('appointment-management.waitlist.book', $first), [
                'starts_at' => "{$this->day} 10:00",
                'staff_id' => $this->stylist->id,
            ])
            ->assertRedirect();

        $first->refresh();
        $this->assertSame(WaitlistEntry::STATUS_BOOKED, $first->status);
        $booked = Appointment::query()->findOrFail($first->appointment_id);
        $this->assertSame($first->customer_id, (int) $booked->customer_id);
        $this->assertSame("{$this->day} 10:00", $booked->starts_at->format('Y-m-d H:i'));
        $this->assertSame(Appointment::STATUS_CONFIRMED, $booked->status);

        // The slot is now taken, so the second customer cannot be booked into it as well.
        $this->actingAs($this->owner)
            ->post(route('appointment-management.waitlist.book', $second), [
                'starts_at' => "{$this->day} 10:00",
                'staff_id' => $this->stylist->id,
            ])
            ->assertSessionHasErrors();
        $this->assertSame(WaitlistEntry::STATUS_WAITING, $second->fresh()->status);
    }

    public function test_entries_can_be_removed_and_past_days_expire(): void
    {
        $entry = $this->wait();

        $this->actingAs($this->owner)
            ->post(route('appointment-management.waitlist.cancel', $entry))
            ->assertSessionHas('status', 'Removed from the waitlist.');
        $this->assertSame(WaitlistEntry::STATUS_CANCELLED, $entry->fresh()->status);

        $old = $this->wait();
        $old->forceFill(['preferred_date' => now()->subDays(3)->toDateString()])->save();

        $this->artisan('waitlist:expire')->assertSuccessful();
        $this->assertSame(WaitlistEntry::STATUS_EXPIRED, $old->fresh()->status);
    }

    public function test_other_salons_cannot_touch_the_waitlist(): void
    {
        $entry = $this->wait();
        [, $otherOwner] = $this->makeSalon();

        $this->actingAs($otherOwner)
            ->post(route('appointment-management.waitlist.cancel', $entry))
            ->assertNotFound();

        $this->assertSame(WaitlistEntry::STATUS_WAITING, $entry->fresh()->status);
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

    private function wait(?string $from = null, ?string $to = null): WaitlistEntry
    {
        return WaitlistEntry::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->makeCustomer($this->tenant, $this->branch)->id,
            'service_id' => $this->service->id,
            'preferred_date' => $this->day,
            'preferred_from' => $from,
            'preferred_to' => $to,
            'status' => WaitlistEntry::STATUS_WAITING,
            'created_by' => $this->owner->id,
        ])->load('customer');
    }
}
