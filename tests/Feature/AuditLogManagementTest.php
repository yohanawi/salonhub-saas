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
use Illuminate\Support\Facades\Hash;
use LogicException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuditLogManagementTest extends TestCase
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
            ->givePermissionTo(['audit-log.view', 'audit-log.view-branch']);
    }

    public function test_owner_can_view_audit_logs_and_details(): void
    {
        [$tenant, $owner, $branch] = $this->tenantOwner('Glow Audit Lounge');
        $auditLog = $this->auditLog($tenant, $branch, $owner, [
            'description' => 'Hair Cut service price changed.',
            'metadata' => ['record_label' => 'Hair Cut'],
        ]);

        $this->actingAs($owner)
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('Audit Logs')
            ->assertSee('Hair Cut');

        $this->actingAs($owner)
            ->get(route('audit-logs.show', $auditLog))
            ->assertOk()
            ->assertSee('Activity Details')
            ->assertSee('Hair Cut service price changed.')
            ->assertSee('Old value')
            ->assertSee('New value');
    }

    public function test_tenant_users_cannot_view_other_tenant_audit_logs(): void
    {
        [$tenant, $owner, $branch] = $this->tenantOwner('Tenant A');
        [, $otherOwner] = $this->tenantOwner('Tenant B');
        $auditLog = $this->auditLog($tenant, $branch, $owner, [
            'description' => 'Private tenant activity.',
            'metadata' => ['record_label' => 'Private Service'],
        ]);

        $this->actingAs($otherOwner)
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertDontSee('Private tenant activity.')
            ->assertDontSee('Private Service');

        $this->actingAs($otherOwner)
            ->get(route('audit-logs.show', $auditLog))
            ->assertForbidden();
    }

    public function test_branch_manager_only_sees_assigned_branch_audit_logs(): void
    {
        [$tenant, $owner, $branch] = $this->tenantOwner('Branch Audit Salon');
        $otherBranch = $this->branch($tenant, 'Kandy');
        $manager = User::factory()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'email_verified_at' => now(),
            'onboarded_at' => now(),
        ]);
        $manager->assignRole('Branch Manager');
        $manager->branches()->attach($branch->id, ['tenant_id' => $tenant->id]);

        $visibleLog = $this->auditLog($tenant, $branch, $owner, [
            'description' => 'Assigned branch activity.',
            'metadata' => ['record_label' => 'Assigned Branch'],
        ]);
        $hiddenLog = $this->auditLog($tenant, $otherBranch, $owner, [
            'description' => 'Other branch activity.',
            'metadata' => ['record_label' => 'Other Branch'],
        ]);

        $this->actingAs($manager)
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('Assigned Branch')
            ->assertDontSee('Other Branch');

        $this->actingAs($manager)->get(route('audit-logs.show', $visibleLog))->assertOk();
        $this->actingAs($manager)->get(route('audit-logs.show', $hiddenLog))->assertForbidden();
    }

    public function test_failed_login_creates_authentication_audit_log(): void
    {
        [$tenant, $owner] = $this->tenantOwner('Login Audit Salon');
        $owner->forceFill(['password' => Hash::make('correct-password')])->save();

        $this->post(route('login'), [
            'email' => $owner->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'action' => AuditLog::ACTION_FAILED_LOGIN,
            'event' => 'auth.failed_login',
            'module' => 'authentication',
        ]);
    }

    public function test_auditable_models_create_append_only_activity_records(): void
    {
        [$tenant, $owner] = $this->tenantOwner('Model Audit Salon');

        $this->actingAs($owner);

        $service = Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Blow Dry',
            'slug' => 'blow-dry-audit-' . fake()->unique()->numberBetween(1000, 9999),
            'duration_minutes' => 45,
            'price' => 2500,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'action' => AuditLog::ACTION_CREATED,
            'event' => 'services.created',
            'module' => 'services',
            'auditable_type' => Service::class,
            'auditable_id' => $service->id,
        ]);

        $auditLog = AuditLog::withoutTenantScope()
            ->where('auditable_type', Service::class)
            ->where('auditable_id', $service->id)
            ->firstOrFail();

        $this->expectException(LogicException::class);
        $auditLog->delete();
    }

    private function tenantOwner(string $name): array
    {
        $tenant = Tenant::create([
            'name' => $name,
            'slug' => str($name)->slug() . '-' . fake()->unique()->numberBetween(1000, 9999),
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

        $branch = $this->branch($tenant, 'Main Branch', true);

        return [$tenant, $owner, $branch];
    }

    private function branch(Tenant $tenant, string $name, bool $isMain = false): Branch
    {
        return Branch::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'code' => strtoupper(str($name)->substr(0, 3)) . '-' . fake()->unique()->numberBetween(1000, 9999),
            'status' => Branch::STATUS_ACTIVE,
            'is_active' => true,
            'is_main' => $isMain,
            'country' => 'LK',
            'currency' => 'LKR',
            'timezone' => 'Asia/Colombo',
        ]);
    }

    private function auditLog(Tenant $tenant, Branch $branch, User $user, array $overrides = []): AuditLog
    {
        return AuditLog::withoutTenantScope()->create(array_merge([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'action' => AuditLog::ACTION_UPDATED,
            'event' => 'services.updated',
            'module' => 'services',
            'description' => 'Service changed.',
            'auditable_type' => Service::class,
            'auditable_id' => 100,
            'old_values' => ['price' => 2000],
            'new_values' => ['price' => 2500],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Feature Test',
            'device' => 'Feature Test',
            'url' => 'http://localhost/services/100',
            'request_method' => 'PATCH',
            'metadata' => ['record_label' => 'Service #100'],
            'created_at' => now(),
        ], $overrides));
    }
}
