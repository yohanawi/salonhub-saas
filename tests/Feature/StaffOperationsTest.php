<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentFeedback;
use App\Models\MessageLog;
use App\Models\StaffAttendance;
use App\Models\StaffShift;
use App\Models\StaffTimeOff;
use App\Models\User;
use App\Services\Settings\SettingService;
use App\Services\Staff\FeedbackService;
use App\Services\StaffAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Tests\Feature\Concerns\BuildsSalonFixtures;
use Tests\TestCase;

class StaffOperationsTest extends TestCase
{
    use BuildsSalonFixtures;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        Queue::fake();
    }

    public function test_roster_overrides_the_weekly_schedule_and_drives_availability(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        $nimal = $this->makeStaff($tenant, $branch);
        $kamala = $this->makeStaff($tenant, $branch);
        $monday = CarbonImmutable::now('Asia/Colombo')->addWeek()->startOfWeek();
        $this->weekly($nimal, $branch, [1, 2, 3, 4, 5]);

        $availability = app(StaffAvailabilityService::class);
        $this->assertSame(['09:00:00', '17:00:00'], $availability->workingWindow($nimal, $branch->id, $monday));
        $this->assertNull($availability->workingWindow($kamala, $branch->id, $monday));

        $this->actingAs($owner)->get(route('staff-management.roster.index', ['branch_id' => $branch->id, 'week' => $monday->toDateString()]))
            ->assertOk()->assertSee('Shift Roster')->assertSee('09:00–17:00');

        // Give Kamala a Monday shift, then move Nimal's Monday to Tuesday by drag & drop.
        $this->actingAs($owner)->postJson(route('staff-management.roster.save'), [
            'branch_id' => $branch->id, 'staff_id' => $kamala->id, 'date' => $monday->toDateString(), 'action' => 'set', 'start_time' => '12:00', 'end_time' => '20:00',
        ])->assertOk();
        $this->assertSame(['12:00:00', '20:00:00'], $availability->workingWindow($kamala, $branch->id, $monday));

        $this->actingAs($owner)->postJson(route('staff-management.roster.move'), [
            'branch_id' => $branch->id, 'from_staff_id' => $nimal->id, 'from_date' => $monday->toDateString(), 'to_staff_id' => $kamala->id, 'to_date' => $monday->addDay()->toDateString(),
        ])->assertOk();

        $this->assertNull($availability->workingWindow($nimal, $branch->id, $monday));
        $this->assertSame(['09:00:00', '17:00:00'], $availability->workingWindow($kamala, $branch->id, $monday->addDay()));
        $this->assertTrue($availability->isAvailableAt($kamala, $branch, $monday->setTime(13, 0), $monday->setTime(14, 0)));
        $this->assertFalse($availability->isAvailableAt($nimal, $branch, $monday->setTime(10, 0), $monday->setTime(11, 0)));

        // Clearing falls back to the weekly schedule; bad times are refused.
        $this->actingAs($owner)->postJson(route('staff-management.roster.save'), ['branch_id' => $branch->id, 'staff_id' => $nimal->id, 'date' => $monday->toDateString(), 'action' => 'clear'])->assertOk();
        $this->assertSame(['09:00:00', '17:00:00'], $availability->workingWindow($nimal, $branch->id, $monday));
        $this->actingAs($owner)->postJson(route('staff-management.roster.save'), [
            'branch_id' => $branch->id, 'staff_id' => $nimal->id, 'date' => $monday->toDateString(), 'action' => 'set', 'start_time' => '17:00', 'end_time' => '09:00',
        ])->assertStatus(422);

        // Copying last week onto the following week materialises the shifts.
        $this->actingAs($owner)->post(route('staff-management.roster.copy', ['branch_id' => $branch->id, 'week' => $monday->addWeek()->toDateString()]))->assertRedirect();
        $this->assertTrue(StaffShift::withoutTenantScope()->where('staff_id', $kamala->id)->whereDate('shift_date', $monday->addWeek()->toDateString())->exists());

        $beautician = User::factory()->create(['tenant_id' => $tenant->id, 'email_verified_at' => now()]);
        $beautician->assignRole('Beautician');
        $this->actingAs($beautician)->get(route('staff-management.roster.index'))->assertForbidden();
    }

    public function test_staff_clock_in_and_out_and_managers_correct_attendance(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'email_verified_at' => now()]);
        $user->assignRole('Beautician');
        $staff = $this->makeStaff($tenant, $branch, $user);
        $now = CarbonImmutable::now('Asia/Colombo');

        // Rostered to start an hour ago, so clocking in now is late.
        StaffShift::withoutTenantScope()->create([
            'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'staff_id' => $staff->id, 'shift_date' => $now->toDateString(),
            'start_time' => $now->subHour()->format('H:i'), 'end_time' => $now->addHours(2)->format('H:i'),
        ]);

        $this->actingAs($user)->get(route('profile.attendance'))->assertOk()->assertSee('Clock In');
        $this->actingAs($user)->post(route('profile.attendance.clock-in'))->assertRedirect()->assertSessionHas('status');
        $this->actingAs($user)->post(route('profile.attendance.clock-in'))->assertSessionHasErrors('attendance');

        $record = StaffAttendance::withoutTenantScope()->where('staff_id', $staff->id)->firstOrFail();
        $this->assertSame(StaffAttendance::STATUS_LATE, $record->status);
        $this->assertGreaterThanOrEqual(59, $record->late_minutes);

        $this->actingAs($user)->post(route('profile.attendance.clock-out'), ['break_minutes' => 0])->assertRedirect();
        $this->assertNotNull($record->fresh()->clock_out_at);

        // Manager records yesterday for the same person.
        $yesterday = $now->subDay()->toDateString();
        $this->actingAs($owner)->post(route('staff-management.attendance.store', ['branch_id' => $branch->id]), [
            'staff_id' => $staff->id, 'date' => $yesterday, 'status' => 'present', 'clock_in' => '09:00', 'clock_out' => '17:30', 'break_minutes' => 30,
        ])->assertRedirect();
        $corrected = StaffAttendance::withoutTenantScope()->where('staff_id', $staff->id)->whereDate('work_date', $yesterday)->firstOrFail();
        $this->assertSame(480, $corrected->worked_minutes);
        $this->assertSame('manual', $corrected->source);

        $this->actingAs($owner)->get(route('staff-management.attendance.index', ['branch_id' => $branch->id]))->assertOk()->assertSee($staff->full_name)->assertSee('Monthly Summary');
        $xlsx = $this->actingAs($owner)->get(route('staff-management.attendance.index', ['branch_id' => $branch->id, 'format' => 'xlsx']))->assertOk();
        $this->assertStringStartsWith('PK', $xlsx->getContent());
        $this->actingAs($user)->get(route('staff-management.attendance.index'))->assertForbidden();
    }

    public function test_leave_request_approval_and_availability(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'email_verified_at' => now()]);
        $user->assignRole('Beautician');
        $staff = $this->makeStaff($tenant, $branch, $user);
        $this->weekly($staff, $branch, [1, 2, 3, 4, 5, 6, 7]);
        $day = CarbonImmutable::now('Asia/Colombo')->addDays(10)->startOfDay();
        $availability = app(StaffAvailabilityService::class);

        $this->actingAs($user)->post(route('profile.leave.store'), ['type' => 'annual_leave', 'start_date' => $day->toDateString(), 'end_date' => $day->addDay()->toDateString(), 'reason' => 'Family trip'])->assertRedirect();
        $leave = StaffTimeOff::withoutTenantScope()->where('staff_id', $staff->id)->firstOrFail();
        $this->assertSame('pending', $leave->status);

        // A pending request already blocks bookings; overlapping requests are refused.
        $this->assertFalse($availability->isAvailableAt($staff, $branch, $day->setTime(10, 0), $day->setTime(11, 0)));
        $this->actingAs($user)->post(route('profile.leave.store'), ['type' => 'sick_leave', 'start_date' => $day->addDay()->toDateString()])->assertSessionHasErrors('start_date');

        $this->actingAs($owner)->get(route('staff-management.leave.index'))->assertOk()->assertSee('Family trip')->assertSee('1 pending');
        $this->actingAs($owner)->post(route('staff-management.leave.review', $leave), ['decision' => 'reject', 'review_notes' => 'Busy week'])->assertRedirect();
        $this->assertSame('rejected', $leave->fresh()->status);
        $this->assertTrue($availability->isAvailableAt($staff, $branch, $day->setTime(10, 0), $day->setTime(11, 0)));

        // Ask again, approve, then the staff member withdraws it before it starts.
        $this->actingAs($user)->post(route('profile.leave.store'), ['type' => 'annual_leave', 'start_date' => $day->toDateString()])->assertRedirect();
        $second = StaffTimeOff::withoutTenantScope()->where('staff_id', $staff->id)->where('status', 'pending')->firstOrFail();
        $this->actingAs($owner)->post(route('staff-management.leave.review', $second), ['decision' => 'approve'])->assertRedirect();
        $this->actingAs($owner)->post(route('staff-management.leave.review', $second), ['decision' => 'approve'])->assertSessionHasErrors('status');
        $this->actingAs($user)->get(route('profile.leave'))->assertOk()->assertSee('Approved');
        $this->actingAs($user)->post(route('profile.leave.cancel', $second))->assertRedirect();
        $this->assertSame('cancelled', $second->fresh()->status);

        // Managers can record leave directly (approved).
        $this->actingAs($owner)->post(route('staff-management.leave.store'), ['staff_id' => $staff->id, 'type' => 'sick_leave', 'start_date' => $day->addDays(5)->toDateString(), 'half_day' => 1])->assertRedirect();
        $this->assertSame('approved', StaffTimeOff::withoutTenantScope()->where('staff_id', $staff->id)->where('type', 'sick_leave')->value('status'));
        $this->actingAs($user)->get(route('staff-management.leave.index'))->assertForbidden();
    }

    public function test_customers_rate_their_visit_through_a_signed_link(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        $this->enableSms($tenant, $owner);
        app(SettingService::class)->setMany($tenant, null, ['notification.send_feedback_request' => true], $owner);
        $staff = $this->makeStaff($tenant, $branch);
        $customer = $this->makeCustomer($tenant, $branch);

        $appointment = Appointment::withoutTenantScope()->create([
            'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'customer_id' => $customer->id, 'staff_id' => $staff->id,
            'appointment_number' => 'APT-' . fake()->unique()->numberBetween(100000, 999999),
            'starts_at' => now()->subHour(), 'ends_at' => now(), 'status' => Appointment::STATUS_IN_PROGRESS,
        ]);

        $this->actingAs($owner)->post(route('appointment-management.appointments.complete', $appointment))->assertRedirect();

        $feedback = AppointmentFeedback::withoutTenantScope()->where('appointment_id', $appointment->id)->firstOrFail();
        $this->assertNotNull($feedback->requested_at);
        $this->assertStringContainsString('/feedback/' . $feedback->id, MessageLog::withoutTenantScope()->where('event', 'feedback_request')->value('body'));

        $url = app(FeedbackService::class)->url($feedback);
        auth()->logout();
        $this->get($url)->assertOk()->assertSee('How was your visit');
        $this->get(route('feedback.show', ['feedback' => $feedback->id]))->assertForbidden();

        $submit = URL::temporarySignedRoute('feedback.store', now()->addDay(), ['feedback' => $feedback->id]);
        $this->post($submit, ['comment' => 'No stars'])->assertSessionHasErrors('rating');
        $this->post($submit, ['rating' => 4, 'comment' => 'Lovely cut'])->assertRedirect();

        $feedback->refresh();
        $this->assertSame(4, $feedback->rating);
        $this->assertSame('Lovely cut', $feedback->comment);
        $this->post($submit, ['rating' => 1])->assertSessionHasErrors('rating');
        $this->get($url)->assertOk()->assertSee('Thank you for your feedback');
    }

    private function weekly($staff, $branch, array $days): void
    {
        foreach ($days as $day) {
            $staff->schedules()->create(['tenant_id' => $staff->tenant_id, 'branch_id' => $branch->id, 'day_of_week' => $day, 'is_working' => true, 'start_time' => '09:00', 'end_time' => '17:00']);
        }
    }
}
