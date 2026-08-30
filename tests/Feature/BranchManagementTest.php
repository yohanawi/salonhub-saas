<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchSpecialHour;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BranchContext;
use App\Services\BranchService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BranchManagementTest extends TestCase
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

        Role::firstOrCreate(['name' => 'Branch Manager', 'guard_name' => 'web'])
            ->givePermissionTo(['branches.view', 'branches.update', 'branches.manage_hours', 'reports.view_branch']);

        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
    }

    public function test_owner_can_create_branch_until_subscription_limit(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner(maxBranches: 1);

        $this->actingAs($owner)
            ->post(route('branches.store'), $this->branchPayload(['code' => 'MAIN']))
            ->assertRedirect();

        $this->assertDatabaseHas('branches', [
            'tenant_id' => $tenant->id,
            'code' => 'MAIN',
            'is_main' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('branches.store'), $this->branchPayload(['name' => 'Second Branch', 'code' => 'SEC']))
            ->assertSessionHasErrors('branch');
    }

    public function test_cross_tenant_branch_access_is_forbidden(): void
    {
        [, $owner] = $this->tenantWithOwner();
        [$otherTenant] = $this->tenantWithOwner();
        $otherBranch = $this->createBranch($otherTenant, ['code' => 'OTH']);

        $this->actingAs($owner)
            ->get(route('branches.show', $otherBranch))
            ->assertForbidden();
    }

    public function test_manager_can_view_assigned_branch_but_not_unassigned_branch(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $assignedBranch = $this->createBranch($tenant, ['code' => 'ASG']);
        $unassignedBranch = $this->createBranch($tenant, ['name' => 'Unassigned', 'code' => 'UNA']);

        $manager = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);
        $manager->assignRole('Branch Manager');
        $assignedBranch->users()->attach($manager->id, ['tenant_id' => $tenant->id]);

        $this->actingAs($manager)
            ->get(route('branches.show', $assignedBranch))
            ->assertOk();

        $this->actingAs($manager)
            ->get(route('branches.show', $unassignedBranch))
            ->assertForbidden();
    }

    public function test_branch_switching_validates_assignment(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $assignedBranch = $this->createBranch($tenant, ['code' => 'ASG']);
        $unassignedBranch = $this->createBranch($tenant, ['name' => 'Unassigned', 'code' => 'UNA']);

        $manager = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);
        $manager->assignRole('Branch Manager');
        $assignedBranch->users()->attach($manager->id, ['tenant_id' => $tenant->id]);

        $this->actingAs($manager)
            ->post(route('branches.switch', $assignedBranch))
            ->assertRedirect();

        $this->assertSame($assignedBranch->id, session('current_branch_id'));
        $this->assertSame($assignedBranch->id, app(BranchContext::class)->current($manager)?->id);

        $this->actingAs($manager)
            ->post(route('branches.switch', $unassignedBranch))
            ->assertForbidden();
    }

    public function test_setting_new_main_branch_clears_previous_main_branch(): void
    {
        [$tenant] = $this->tenantWithOwner(maxBranches: 5);
        $service = app(BranchService::class);

        $mainBranch = $service->create($tenant, $this->branchPayload(['code' => 'MAIN']));
        $secondBranch = $service->create($tenant, $this->branchPayload([
            'name' => 'Nugegoda Branch',
            'code' => 'NUG',
            'is_main' => true,
        ]));

        $this->assertFalse($mainBranch->fresh()->is_main);
        $this->assertTrue($secondBranch->fresh()->is_main);
    }

    public function test_same_branch_code_is_allowed_across_tenants(): void
    {
        [$tenantA] = $this->tenantWithOwner();
        [$tenantB] = $this->tenantWithOwner();

        $this->createBranch($tenantA, ['code' => 'CMB']);
        $this->createBranch($tenantB, ['code' => 'CMB']);

        $this->assertSame(2, Branch::withoutGlobalScope('tenant')->where('code', 'CMB')->count());
    }

    public function test_super_admin_can_create_branch_for_selected_tenant(): void
    {
        [$tenant] = $this->tenantWithOwner(maxBranches: 2);
        $superAdmin = User::factory()->create([
            'tenant_id' => null,
            'email_verified_at' => now(),
        ]);
        $superAdmin->assignRole('Super Admin');

        $this->actingAs($superAdmin)
            ->post(route('branches.store'), $this->branchPayload([
                'tenant_id' => $tenant->id,
                'name' => 'Admin Created Branch',
                'code' => 'ADM',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('branches', [
            'tenant_id' => $tenant->id,
            'code' => 'ADM',
        ]);
    }

    public function test_branch_can_be_archived_and_restored_with_replacement_main_branch(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner(maxBranches: 3);
        $mainBranch = $this->createBranch($tenant, ['code' => 'MAIN', 'is_main' => true]);
        $replacementBranch = $this->createBranch($tenant, ['name' => 'Replacement', 'code' => 'REP']);

        $this->actingAs($owner)
            ->delete(route('branches.archive', $mainBranch), [
                'replacement_main_branch_id' => $replacementBranch->id,
            ])
            ->assertRedirect(route('branches.index'));

        $this->assertSoftDeleted('branches', ['id' => $mainBranch->id]);
        $this->assertTrue($replacementBranch->fresh()->is_main);

        $this->actingAs($owner)
            ->post(route('branches.restore', $mainBranch))
            ->assertRedirect(route('branches.show', $mainBranch));

        $this->assertNotSoftDeleted('branches', ['id' => $mainBranch->id]);
        $this->assertTrue($mainBranch->fresh()->is_active);
    }

    public function test_special_hours_can_be_created_and_removed(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $branch = $this->createBranch($tenant, ['code' => 'HOL']);

        $date = now()->addWeek()->toDateString();

        $this->actingAs($owner)
            ->post(route('branches.special-hours.store', $branch), [
                'date' => $date,
                'label' => 'Holiday',
                'is_closed' => 1,
                'note' => 'Closed for maintenance',
            ])
            ->assertRedirect();

        $specialHour = BranchSpecialHour::where('branch_id', $branch->id)->whereDate('date', $date)->first();

        $this->assertNotNull($specialHour);
        $this->assertTrue($specialHour->is_closed);

        $this->actingAs($owner)
            ->delete(route('branches.special-hours.destroy', [$branch, $specialHour]))
            ->assertRedirect();

        $this->assertDatabaseMissing('branch_special_hours', [
            'id' => $specialHour->id,
        ]);
    }

    public function test_branch_report_page_is_available_to_owner(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $branch = $this->createBranch($tenant, ['code' => 'RPT']);

        $this->actingAs($owner)
            ->get(route('branches.reports.show', $branch))
            ->assertOk()
            ->assertSee('Reports')
            ->assertSee('Appointments')
            ->assertSee('Inventory Items');
    }

    private function tenantWithOwner(?int $maxBranches = 3): array
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
            'max_branches' => $maxBranches,
            'max_staff' => 10,
            'features' => ['multi_branch_reports' => true],
            'is_active' => true,
        ]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'trialing',
            'starts_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);

        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);
        $owner->assignRole('Salon Owner');

        return [$tenant, $owner, $plan];
    }

    private function createBranch(Tenant $tenant, array $overrides = []): Branch
    {
        return app(BranchService::class)->create($tenant, $this->branchPayload($overrides));
    }

    private function branchPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Colombo Branch',
            'code' => 'CMB',
            'phone' => '+94770000000',
            'email' => 'branch' . fake()->unique()->numberBetween(1000, 9999) . '@saloonhub.test',
            'address_line_1' => 'No. 10, Main Street',
            'address_line_2' => null,
            'city' => 'Colombo',
            'district' => 'Colombo',
            'postal_code' => '00100',
            'country' => 'LK',
            'currency' => 'LKR',
            'timezone' => 'Asia/Colombo',
            'invoice_prefix' => 'CMB',
            'tax_enabled' => true,
            'tax_name' => 'VAT',
            'tax_rate' => 18,
            'tax_number' => 'VAT-001',
            'is_main' => false,
        ], $overrides);
    }
}
