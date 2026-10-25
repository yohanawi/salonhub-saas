<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Branch;
use App\Models\CommissionPayout;
use App\Models\Customer;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MyAppointmentsAndStatementsTest extends TestCase
{
    use DatabaseTransactions;

    private Tenant $tenant;

    private Branch $branch;

    private Customer $customer;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (RolesPermissionsSeeder::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'Beautician', 'guard_name' => 'web'])
            ->givePermissionTo(['appointments.view', 'payslips.view_own', 'commissions.view_own']);

        $this->tenant = Tenant::create([
            'name' => 'Glow Studio',
            'slug' => fake()->unique()->slug(),
            'email' => fake()->unique()->safeEmail(),
            'status' => 'active',
            'timezone' => 'Asia/Colombo',
            'currency' => 'LKR',
        ]);

        $this->branch = Branch::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Colombo',
            'code' => 'CMB',
            'status' => Branch::STATUS_ACTIVE,
            'is_active' => true,
            'is_main' => true,
            'country' => 'LK',
            'currency' => 'LKR',
            'timezone' => 'Asia/Colombo',
        ]);

        $this->customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUS-000001',
            'first_name' => 'Emma',
            'last_name' => 'Silva',
            'phone' => '0771234567',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $this->service = Service::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Hair Cut',
            'slug' => 'hair-cut',
            'price' => 2500,
            'duration_minutes' => 45,
            'is_active' => true,
        ]);
    }

    private function staffUser(string $code = 'STF-0001'): array
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('Beautician');

        $staff = Staff::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $user->id,
            'employee_code' => $code,
            'first_name' => 'Nadeesha',
            'last_name' => 'Perera',
            'status' => Staff::STATUS_ACTIVE,
            'is_bookable' => true,
        ]);

        return [$user, $staff];
    }

    private function book(Staff $staff, CarbonImmutable $startsAt, string $status = Appointment::STATUS_CONFIRMED, bool $viaServiceLine = false): Appointment
    {
        $appointment = Appointment::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $viaServiceLine ? null : $staff->id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->addMinutes(45),
            'status' => $status,
        ]);

        AppointmentService::create([
            'tenant_id' => $this->tenant->id,
            'appointment_id' => $appointment->id,
            'service_id' => $this->service->id,
            'staff_id' => $staff->id,
            'service_name' => 'Hair Cut',
            'duration_minutes' => 45,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->addMinutes(45),
            'status' => $status,
        ]);

        return $appointment;
    }

    private function localToday(): CarbonImmutable
    {
        return CarbonImmutable::now('Asia/Colombo')->startOfDay();
    }

    public function test_staff_sees_today_and_tomorrow_appointments_only(): void
    {
        [$user, $staff] = $this->staffUser();
        [, $otherStaff] = $this->staffUser('STF-0002');

        $this->book($staff, $this->localToday()->setTime(10, 0));
        $this->book($staff, $this->localToday()->addDay()->setTime(14, 30), viaServiceLine: true);
        $this->book($staff, $this->localToday()->addDays(3)->setTime(9, 0));
        $this->customer->update(['first_name' => 'Other']);
        $this->book($otherStaff, $this->localToday()->setTime(11, 0));

        $response = $this->actingAs($user)->get(route('profile.appointments'))->assertOk();

        $agenda = $response->viewData('agenda');
        $this->assertCount(1, $agenda['today']);
        $this->assertCount(1, $agenda['tomorrow']);
        $this->assertSame('10:00', $agenda['today']->first()->starts_at->format('H:i'));
        $this->assertSame('14:30', $agenda['tomorrow']->first()->starts_at->format('H:i'));
    }

    public function test_account_menu_badge_counts_only_active_appointments_today(): void
    {
        [$user, $staff] = $this->staffUser();

        $this->book($staff, $this->localToday()->setTime(9, 0));
        $this->book($staff, $this->localToday()->setTime(11, 0), Appointment::STATUS_PENDING);
        $this->book($staff, $this->localToday()->setTime(13, 0), Appointment::STATUS_CANCELLED);
        $this->book($staff, $this->localToday()->addDay()->setTime(9, 0));

        $this->actingAs($user)->get(route('profile.show'))
            ->assertOk()
            ->assertSee('My Appointments')
            ->assertSee('title="Appointments today"', false)
            ->assertSee('<span class="badge badge-light-danger badge-circle fw-bold fs-7">2</span>', false)
            ->assertDontSee('My Projects')
            ->assertSee(route('profile.statements'), false);
    }

    public function test_user_without_staff_profile_gets_empty_state_and_no_menu_entry(): void
    {
        $owner = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($owner)->get(route('profile.appointments'))
            ->assertOk()
            ->assertSee("isn't linked to a staff profile", false)
            ->assertDontSee('My Appointments');
    }

    public function test_staff_statements_show_own_payslips_and_payouts_but_not_salon_subscription(): void
    {
        [$user, $staff] = $this->staffUser();
        [, $otherStaff] = $this->staffUser('STF-0002');

        $plan = Plan::create([
            'name' => 'Pro',
            'slug' => fake()->unique()->slug(),
            'price' => 49,
            'billing_period' => 'monthly',
            'features' => [],
            'is_active' => true,
        ]);

        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'price' => 49,
            'billing_period' => 'monthly',
            'starts_at' => now(),
        ]);

        $period = PayrollPeriod::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'October 2026',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'status' => 'approved',
        ]);
        foreach ([
            [$staff, PayrollItem::STATUS_APPROVED, 85000],
            [$staff, PayrollItem::STATUS_CALCULATED, 11111],
            [$otherStaff, PayrollItem::STATUS_APPROVED, 22222],
        ] as [$owner, $status, $net]) {
            $run = PayrollRun::create([
                'tenant_id' => $this->tenant->id,
                'payroll_period_id' => $period->id,
                'status' => PayrollRun::STATUS_APPROVED,
            ]);

            PayrollItem::create([
                'tenant_id' => $this->tenant->id,
                'payroll_period_id' => $period->id,
                'payroll_run_id' => $run->id,
                'staff_id' => $owner->id,
                'gross_pay' => $net,
                'net_pay' => $net,
                'status' => $status,
            ]);
        }

        CommissionPayout::create([
            'tenant_id' => $this->tenant->id,
            'staff_id' => $staff->id,
            'payout_number' => 'CP-0001',
            'period_start' => '2026-10-01',
            'period_end' => '2026-10-31',
            'gross_commission' => 12500,
            'net_payable' => 12500,
            'status' => CommissionPayout::STATUS_PAID,
        ]);

        $this->actingAs($user)->get(route('profile.statements'))
            ->assertOk()
            ->assertSee('My Payslips')
            ->assertSee('October 2026')
            ->assertSee('LKR 85,000.00')
            ->assertDontSee('11,111.00')
            ->assertDontSee('22,222.00')
            ->assertSee('My Commission Payouts')
            ->assertSee('CP-0001')
            ->assertDontSee('$49.00')
            ->assertDontSee('No statements available yet.');
    }
}
