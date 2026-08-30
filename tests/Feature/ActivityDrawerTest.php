<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ActivityDrawerTest extends TestCase
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
    }

    public function test_navbar_activity_drawer_shows_recent_audit_logs(): void
    {
        [$tenant, $owner, $branch] = $this->tenantOwner();

        AuditLog::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'user_id' => $owner->id,
            'action' => AuditLog::ACTION_UPDATED,
            'event' => 'services.updated',
            'module' => 'services',
            'description' => 'Activity drawer service update.',
            'auditable_type' => Service::class,
            'auditable_id' => 77,
            'old_values' => ['price' => 2000],
            'new_values' => ['price' => 2500],
            'ip_address' => '127.0.0.1',
            'metadata' => ['record_label' => 'Drawer Hair Spa'],
            'created_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="kt_activities_toggle"', false)
            ->assertSee('Recent Activity')
            ->assertSee('Drawer Hair Spa')
            ->assertSee('Activity drawer service update.')
            ->assertSee(route('audit-logs.index'), false);
    }

    private function tenantOwner(): array
    {
        $tenant = Tenant::create([
            'name' => 'Drawer Activity Salon',
            'slug' => 'drawer-activity-' . fake()->unique()->numberBetween(1000, 9999),
            'email' => fake()->unique()->safeEmail(),
            'status' => 'active',
            'timezone' => 'Asia/Colombo',
            'currency' => 'LKR',
        ]);

        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
            'onboarded_at' => now(),
        ]);
        $owner->assignRole('Salon Owner');

        $branch = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Branch',
            'code' => 'ACT-' . fake()->unique()->numberBetween(1000, 9999),
            'status' => Branch::STATUS_ACTIVE,
            'is_active' => true,
            'is_main' => true,
            'country' => 'LK',
            'currency' => 'LKR',
            'timezone' => 'Asia/Colombo',
        ]);

        return [$tenant, $owner, $branch];
    }
}
