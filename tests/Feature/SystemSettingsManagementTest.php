<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Settings\SettingService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SystemSettingsManagementTest extends TestCase
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

    public function test_owner_can_update_tenant_settings_and_settings_are_audited(): void
    {
        [$tenant, $owner] = $this->tenantOwner();

        $this->actingAs($owner)
            ->patch(route('settings.update', 'appointments'), [
                'settings' => [
                    'booking.interval_minutes' => 20,
                    'booking.minimum_notice_minutes' => 90,
                    'booking.maximum_advance_days' => 45,
                    'booking.cancellation_cutoff_hours' => 10,
                    'booking.reschedule_cutoff_hours' => 8,
                    'booking.allow_same_day' => 1,
                    'booking.allow_walk_ins' => 0,
                    'booking.auto_confirm' => 1,
                    'booking.require_deposit' => 0,
                    'booking.deposit_percent' => 0,
                    'appointment_status.auto_mark_no_show' => 1,
                    'appointment_status.no_show_grace_minutes' => 15,
                    'appointment_status.allow_staff_reassignment' => 1,
                    'appointment_status.allow_service_modification_after_checkin' => 0,
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('settings', [
            'tenant_id' => $tenant->id,
            'branch_id' => null,
            'group' => 'booking',
            'key' => 'booking.interval_minutes',
            'value' => '20',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'action' => 'settings.updated',
        ]);

        $this->assertSame(20, app(SettingService::class)->get('booking.interval_minutes', $tenant));
        $this->assertFalse(app(SettingService::class)->get('booking.allow_walk_ins', $tenant));
    }

    public function test_branch_override_wins_over_tenant_and_platform_defaults(): void
    {
        [$tenant, $owner, $branch] = $this->tenantOwner();

        app(SettingService::class)->setMany(null, null, ['booking.interval_minutes' => 30], $owner);
        app(SettingService::class)->setMany($tenant, null, ['booking.interval_minutes' => 15], $owner);
        app(SettingService::class)->setMany($tenant, $branch, ['booking.interval_minutes' => 10], $owner);

        $this->assertSame(10, app(SettingService::class)->get('booking.interval_minutes', $tenant, $branch));
        $this->assertSame(15, app(SettingService::class)->get('booking.interval_minutes', $tenant));
    }

    public function test_settings_pages_are_available(): void
    {
        [$tenant, $owner] = $this->tenantOwner();

        $this->actingAs($owner)->get(route('settings.index'))->assertOk()->assertSee('Settings Center');
        $this->actingAs($owner)->get(route('settings.edit', 'general'))->assertOk()->assertSee('Business Details');
        $this->actingAs($owner)->get(route('settings.edit', 'appointments'))->assertOk()->assertSee('Booking Rules');
        $this->actingAs($owner)->get(route('settings.edit', 'sales'))->assertOk()->assertSee('POS, Payments, Taxes &amp; Invoices', false);

        $this->assertSame($tenant->id, $owner->tenant_id);
    }

    private function tenantOwner(): array
    {
        $tenant = Tenant::create([
            'name' => 'Glow Beauty Lounge',
            'slug' => 'glow-beauty-' . fake()->unique()->numberBetween(1000, 9999),
            'email' => fake()->unique()->safeEmail(),
            'status' => 'active',
            'timezone' => 'Asia/Colombo',
            'currency' => 'LKR',
        ]);

        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);
        $owner->assignRole('Salon Owner');

        $branch = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Colombo',
            'code' => 'CMB-' . fake()->unique()->numberBetween(1000, 9999),
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
