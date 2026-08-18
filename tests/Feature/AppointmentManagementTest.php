<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AppointmentManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (RolesPermissionsSeeder::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'Salon Owner', 'guard_name' => 'web'])
            ->givePermissionTo(RolesPermissionsSeeder::PERMISSIONS);

        Role::firstOrCreate(['name' => 'Receptionist', 'guard_name' => 'web'])
            ->givePermissionTo([
                'appointments.view',
                'appointments.create',
                'appointments.update',
                'appointments.cancel',
                'appointments.confirm',
                'appointments.check_in',
                'appointments.no_show',
                'appointments.view_internal_notes',
                'appointments.manage_internal_notes',
            ]);
    }

    public function test_owner_can_create_appointment_with_service_snapshot(): void
    {
        [$tenant, $owner, $branch, $customer, $service, $staff] = $this->bookingSetup();

        $this->actingAs($owner)
            ->post(route('appointment-management.appointments.store'), $this->appointmentPayload($branch, $customer, $service, $staff))
            ->assertRedirect();

        $appointment = Appointment::where('tenant_id', $tenant->id)->firstOrFail();

        $this->assertStringStartsWith('APT-', $appointment->appointment_number);
        $this->assertSame(Appointment::STATUS_PENDING, $appointment->status);
        $this->assertSame($branch->id, $appointment->branch_id);
        $this->assertSame($customer->id, $appointment->customer_id);
        $this->assertSame('reception', $appointment->booking_source);
        $this->assertEquals('3000.00', $appointment->subtotal);
        $this->assertEquals('2800.00', $appointment->total_amount);

        $this->assertDatabaseHas('appointment_services', [
            'tenant_id' => $tenant->id,
            'appointment_id' => $appointment->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'service_name' => 'Hair Cut',
            'duration_minutes' => 60,
            'unit_price' => 3000,
            'discount_amount' => 200,
            'total_price' => 2800,
        ]);

        $this->assertDatabaseHas('appointment_status_history', [
            'tenant_id' => $tenant->id,
            'appointment_id' => $appointment->id,
            'from_status' => null,
            'to_status' => Appointment::STATUS_PENDING,
            'changed_by' => $owner->id,
        ]);
    }

    public function test_backend_prevents_double_booking_same_staff(): void
    {
        [, $owner, $branch, $customer, $service, $staff] = $this->bookingSetup();

        $this->actingAs($owner)
            ->post(route('appointment-management.appointments.store'), $this->appointmentPayload($branch, $customer, $service, $staff, '2026-08-17 10:00'))
            ->assertRedirect();

        $this->actingAs($owner)
            ->post(route('appointment-management.appointments.store'), $this->appointmentPayload($branch, $customer, $service, $staff, '2026-08-17 10:30'))
            ->assertSessionHasErrors('services.0.staff_id');

        $this->assertSame(1, Appointment::query()->count());
    }

    public function test_status_lifecycle_records_history_and_timestamps(): void
    {
        [, $owner, $branch, $customer, $service, $staff] = $this->bookingSetup();

        $this->actingAs($owner)
            ->post(route('appointment-management.appointments.store'), array_merge($this->appointmentPayload($branch, $customer, $service, $staff), [
                'status' => Appointment::STATUS_PENDING,
            ]))
            ->assertRedirect();

        $appointment = Appointment::firstOrFail();

        $this->actingAs($owner)->post(route('appointment-management.appointments.confirm', $appointment))->assertRedirect();
        $this->actingAs($owner)->post(route('appointment-management.appointments.check-in', $appointment))->assertRedirect();
        $this->actingAs($owner)->post(route('appointment-management.appointments.start', $appointment))->assertRedirect();
        $this->actingAs($owner)->post(route('appointment-management.appointments.complete', $appointment))->assertRedirect();

        $appointment->refresh();

        $this->assertSame(Appointment::STATUS_COMPLETED, $appointment->status);
        $this->assertNotNull($appointment->checked_in_at);
        $this->assertNotNull($appointment->started_at);
        $this->assertNotNull($appointment->completed_at);
        $this->assertSame(5, $appointment->statusHistory()->count());
        $this->assertSame(Appointment::STATUS_COMPLETED, AppointmentService::firstOrFail()->status);
    }

    public function test_appointment_pages_are_available(): void
    {
        [, $owner, $branch, $customer, $service, $staff] = $this->bookingSetup();

        $this->actingAs($owner)
            ->get(route('appointment-management.appointments.index'))
            ->assertOk()
            ->assertSee('Appointment List');

        $this->actingAs($owner)
            ->get(route('appointment-management.appointments.create'))
            ->assertOk()
            ->assertSee('Create Appointment');

        $this->actingAs($owner)
            ->post(route('appointment-management.appointments.store'), $this->appointmentPayload($branch, $customer, $service, $staff))
            ->assertRedirect();

        $appointment = Appointment::firstOrFail();

        $this->actingAs($owner)
            ->get(route('appointment-management.appointments.show', $appointment))
            ->assertOk()
            ->assertSee($appointment->appointment_number);

        $this->actingAs($owner)
            ->get(route('appointment-management.appointments.calendar', ['date' => '2026-08-17']))
            ->assertOk()
            ->assertSee('Day / Staff View');
    }

    private function bookingSetup(): array
    {
        $tenant = Tenant::create([
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
            'email' => fake()->unique()->safeEmail(),
            'status' => 'active',
            'timezone' => 'Asia/Colombo',
            'currency' => 'LKR',
        ]);

        $plan = Plan::create([
            'name' => fake()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'price' => 0,
            'billing_period' => 'trial',
            'max_branches' => 10,
            'max_staff' => 10,
            'max_users' => 10,
            'max_customers' => null,
            'features' => [
                'appointment_calendar' => true,
                'customer_management' => true,
                'services' => true,
            ],
            'is_active' => true,
        ]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'trialing',
            'price' => 0,
            'billing_period' => 'trial',
            'entitlements' => $plan->entitlementSnapshot(),
            'starts_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);

        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);
        $owner->assignRole('Salon Owner');

        $branch = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Colombo',
            'code' => 'CMB',
            'status' => Branch::STATUS_ACTIVE,
            'is_active' => true,
            'is_main' => true,
            'country' => 'LK',
            'currency' => 'LKR',
            'timezone' => 'Asia/Colombo',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'customer_code' => 'CUS-000001',
            'first_name' => 'Emma',
            'last_name' => 'Silva',
            'phone' => '0771234567',
            'email' => 'emma@example.test',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $service = Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Hair Cut',
            'slug' => 'hair-cut',
            'price' => 2500,
            'duration_minutes' => 45,
            'default_price' => 2500,
            'default_duration_minutes' => 45,
            'is_active' => true,
        ]);

        $service->branches()->attach($branch->id, [
            'tenant_id' => $tenant->id,
            'price' => 2500,
            'duration_minutes' => 45,
            'is_active' => true,
        ]);

        $staff = Staff::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'STF-0001',
            'first_name' => 'Nadeesha',
            'last_name' => 'Perera',
            'job_title' => 'Senior Stylist',
            'status' => Staff::STATUS_ACTIVE,
            'is_bookable' => true,
            'show_online' => true,
        ]);

        $staff->branches()->attach($branch->id, [
            'tenant_id' => $tenant->id,
            'is_primary' => true,
            'status' => 'active',
        ]);

        $staff->services()->attach($service->id, [
            'tenant_id' => $tenant->id,
            'custom_duration_minutes' => 60,
            'custom_price' => 3000,
            'status' => 'active',
        ]);

        $staff->schedules()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'day_of_week' => 1,
            'is_working' => true,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        return [$tenant, $owner, $branch, $customer, $service, $staff];
    }

    private function appointmentPayload(Branch $branch, Customer $customer, Service $service, Staff $staff, string $startsAt = '2026-08-17 10:00'): array
    {
        return [
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'booking_source' => 'reception',
            'status' => Appointment::STATUS_PENDING,
            'starts_at' => $startsAt,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'customer_notes' => 'Use fragrance-free products.',
            'internal_notes' => 'Customer prefers senior stylist.',
            'services' => [
                [
                    'service_id' => $service->id,
                    'staff_id' => $staff->id,
                    'discount_amount' => 200,
                ],
            ],
        ];
    }
}
