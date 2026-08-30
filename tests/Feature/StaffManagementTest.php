<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\StaffAvailabilityService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StaffManagementTest extends TestCase
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
            ->givePermissionTo(['staff.view']);

        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
    }

    public function test_owner_can_create_staff_with_branch_service_schedule_break_and_time_off(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner(maxStaff: 5);
        $branch = $this->branch($tenant, 'Colombo', 'CMB');
        $service = $this->service($tenant, 'Hair Coloring');

        $this->actingAs($owner)
            ->post(route('staff-management.staff.store'), $this->staffPayload($branch, $service))
            ->assertRedirect();

        $staff = Staff::where('tenant_id', $tenant->id)->where('employee_code', 'STF-0001')->firstOrFail();

        $this->assertTrue($staff->branches()->whereKey($branch->id)->exists());
        $this->assertTrue($staff->services()->whereKey($service->id)->exists());
        $this->assertDatabaseHas('staff_schedules', [
            'tenant_id' => $tenant->id,
            'staff_id' => $staff->id,
            'branch_id' => $branch->id,
            'day_of_week' => 1,
        ]);
        $this->assertDatabaseHas('staff_breaks', [
            'tenant_id' => $tenant->id,
            'staff_id' => $staff->id,
            'branch_id' => $branch->id,
            'title' => 'Lunch',
        ]);
        $this->assertDatabaseHas('staff_time_off', [
            'tenant_id' => $tenant->id,
            'staff_id' => $staff->id,
            'type' => 'annual_leave',
        ]);
    }

    public function test_staff_availability_respects_branch_service_schedule_break_and_time_off(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner(maxStaff: 5);
        $branch = $this->branch($tenant, 'Colombo', 'CMB');
        $service = $this->service($tenant, 'Haircut');

        $this->actingAs($owner)
            ->post(route('staff-management.staff.store'), $this->staffPayload($branch, $service))
            ->assertRedirect();

        $staff = Staff::where('tenant_id', $tenant->id)->where('employee_code', 'STF-0001')->firstOrFail();
        $availability = app(StaffAvailabilityService::class);

        $this->assertTrue($availability->canWorkAtBranch($staff, $branch));
        $this->assertTrue($availability->canPerformService($staff, $service));
        $this->assertTrue($availability->isAvailableAt($staff, $branch, now()->next('Monday')->setTime(10, 0), now()->next('Monday')->setTime(10, 30)));
        $this->assertFalse($availability->isAvailableAt($staff, $branch, now()->next('Monday')->setTime(13, 30), now()->next('Monday')->setTime(14, 0)));
        $this->assertFalse($availability->isAvailableAt($staff, $branch, now()->parse('2026-08-20 10:00'), now()->parse('2026-08-20 10:30')));
    }

    public function test_tenant_cannot_assign_other_tenant_branch_or_service(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner(maxStaff: 5);
        [$otherTenant] = $this->tenantWithOwner(maxStaff: 5);
        $branch = $this->branch($otherTenant, 'Other Branch', 'OTH');
        $service = $this->service($otherTenant, 'Other Service');

        $this->actingAs($owner)
            ->post(route('staff-management.staff.store'), $this->staffPayload($branch, $service))
            ->assertSessionHasErrors(['branches', 'services']);

        $this->assertDatabaseMissing('staff', [
            'tenant_id' => $tenant->id,
            'employee_code' => 'STF-0001',
        ]);
    }

    public function test_subscription_staff_limit_is_enforced(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner(maxStaff: 1);
        $branch = $this->branch($tenant, 'Colombo', 'CMB');
        $service = $this->service($tenant, 'Haircut');

        Staff::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'STF-EXISTING',
            'first_name' => 'Existing',
            'last_name' => 'Stylist',
            'job_title' => 'Stylist',
            'status' => Staff::STATUS_ACTIVE,
            'is_bookable' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('staff-management.staff.store'), $this->staffPayload($branch, $service))
            ->assertSessionHasErrors('staff');
    }

    public function test_super_admin_can_open_staff_management_for_selected_tenant(): void
    {
        [$tenant] = $this->tenantWithOwner(maxStaff: 5);
        $branch = $this->branch($tenant, 'Colombo', 'CMB');
        $service = $this->service($tenant, 'Haircut');
        $superAdmin = User::factory()->create([
            'tenant_id' => null,
            'email_verified_at' => now(),
        ]);
        $superAdmin->assignRole('Super Admin');

        $this->actingAs($superAdmin)
            ->get(route('staff-management.staff.index'))
            ->assertOk()
            ->assertSee('Staff Directory')
            ->assertSee('data-staff-realtime-search', false)
            ->assertSee('staff-search-spinner')
            ->assertSee('data-kt-menu-trigger="click"', false)
            ->assertSee('Apply Filters');

        $this->actingAs($superAdmin)
            ->post(route('staff-management.staff.store'), array_merge($this->staffPayload($branch, $service), [
                'tenant_id' => $tenant->id,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('staff', [
            'tenant_id' => $tenant->id,
            'employee_code' => 'STF-0001',
        ]);
    }

    private function tenantWithOwner(int $maxStaff): array
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
            'max_staff' => $maxStaff,
            'max_users' => 10,
            'max_customers' => null,
            'features' => ['services' => true],
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

        return [$tenant, $owner];
    }

    private function branch(Tenant $tenant, string $name, string $code): Branch
    {
        return Branch::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'code' => $code,
            'status' => Branch::STATUS_ACTIVE,
            'is_active' => true,
            'is_main' => false,
            'country' => 'LK',
            'currency' => 'LKR',
            'timezone' => 'Asia/Colombo',
        ]);
    }

    private function service(Tenant $tenant, string $name): Service
    {
        return Service::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'slug' => str($name)->slug(),
            'price' => 2500,
            'duration_minutes' => 45,
            'default_price' => 2500,
            'default_duration_minutes' => 45,
            'is_active' => true,
        ]);
    }

    private function staffPayload(Branch $branch, Service $service): array
    {
        return [
            'employee_code' => 'STF-0001',
            'first_name' => 'Nadeesha',
            'last_name' => 'Perera',
            'email' => 'nadeesha@example.test',
            'phone' => '+94770000000',
            'job_title' => 'Senior Stylist',
            'status' => Staff::STATUS_ACTIVE,
            'is_bookable' => 1,
            'show_online' => 1,
            'commission_type' => 'percentage',
            'commission_value' => 15,
            'primary_branch_id' => $branch->id,
            'branches' => [
                $branch->id => [
                    'enabled' => 1,
                    'status' => 'active',
                ],
            ],
            'services' => [
                $service->id => [
                    'enabled' => 1,
                    'custom_duration_minutes' => 60,
                    'custom_price' => 3000,
                    'status' => 'active',
                ],
            ],
            'schedules' => [
                [
                    'branch_id' => $branch->id,
                    'day_of_week' => 1,
                    'is_working' => 1,
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                ],
            ],
            'breaks' => [
                [
                    'branch_id' => $branch->id,
                    'day_of_week' => 1,
                    'start_time' => '13:00',
                    'end_time' => '14:00',
                    'title' => 'Lunch',
                ],
            ],
            'time_off' => [
                [
                    'start_datetime' => '2026-08-20 00:00',
                    'end_datetime' => '2026-08-21 23:59',
                    'type' => 'annual_leave',
                    'status' => 'approved',
                    'reason' => 'Annual leave',
                ],
            ],
            'commission_settings' => [
                [
                    'service_id' => $service->id,
                    'commission_type' => 'percentage',
                    'commission_value' => 20,
                ],
            ],
        ];
    }
}
