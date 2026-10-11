<?php

namespace Tests\Feature;

use App\Livewire\Permission\PermissionModal;
use App\Livewire\Permission\RoleList;
use App\Livewire\Permission\RoleModal;
use App\Livewire\User\AddUserModal;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserManagementSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['Super Admin', 'Salon Owner', 'Receptionist'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        Permission::firstOrCreate(['name' => 'dashboard.view', 'guard_name' => 'web']);
    }

    public function test_sample_api_routes_are_removed(): void
    {
        $this->getJson('/api/v1/users')->assertNotFound();
        $this->postJson('/api/v1/users', ['name' => 'X', 'email' => 'x@example.com'])->assertNotFound();
        $this->putJson('/api/v1/users/1', ['role' => 'Super Admin'])->assertNotFound();
        $this->getJson('/api/v1/roles')->assertNotFound();
        $this->getJson('/api/v1/permissions')->assertNotFound();
    }

    public function test_tenant_users_cannot_open_user_management_pages(): void
    {
        foreach (['Salon Owner', 'Receptionist'] as $role) {
            $user = $this->tenantUser($role);

            $this->actingAs($user)->get(route('user-management.users.index'))->assertForbidden();
            $this->actingAs($user)->get(route('user-management.roles.index'))->assertForbidden();
            $this->actingAs($user)->get(route('user-management.permissions.index'))->assertForbidden();
        }
    }

    public function test_super_admin_can_open_user_management_pages(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->get(route('user-management.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('user-management.roles.index'))->assertOk();
        $this->actingAs($admin)->get(route('user-management.permissions.index'))->assertOk();
    }

    public function test_tenant_user_cannot_use_livewire_user_management_components(): void
    {
        $receptionist = $this->tenantUser('Receptionist');

        $this->actingAs($receptionist);

        foreach ([AddUserModal::class, RoleModal::class, RoleList::class, PermissionModal::class] as $component) {
            Livewire::test($component)->assertForbidden();
        }
    }

    public function test_super_admin_creates_user_with_unguessable_password(): void
    {
        Notification::fake();

        $this->actingAs($this->superAdmin());

        Livewire::test(AddUserModal::class)
            ->set('name', 'New Person')
            ->set('email', 'new.person@example.com')
            ->set('role', 'Receptionist')
            ->call('submit')
            ->assertHasNoErrors();

        $created = User::where('email', 'new.person@example.com')->firstOrFail();

        $this->assertTrue($created->hasRole('Receptionist'));
        $this->assertFalse(Hash::check('new.person@example.com', $created->password));
    }

    public function test_user_modal_rejects_duplicate_email_and_unknown_role(): void
    {
        $existing = $this->tenantUser('Receptionist');

        $this->actingAs($this->superAdmin());

        Livewire::test(AddUserModal::class)
            ->set('name', 'Duplicate')
            ->set('email', $existing->email)
            ->set('role', 'Not A Role')
            ->call('submit')
            ->assertHasErrors(['email', 'role']);
    }

    public function test_built_in_roles_and_permissions_cannot_be_renamed_or_deleted(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(RoleModal::class)
            ->call('mountRole', 'Receptionist')
            ->set('name', 'Front Desk')
            ->call('submit')
            ->assertHasErrors(['name']);

        $this->assertDatabaseHas('roles', ['name' => 'Receptionist']);

        Livewire::test(PermissionModal::class)
            ->call('mountPermission', 'dashboard.view')
            ->set('name', 'dashboard.renamed')
            ->call('submit')
            ->assertHasErrors(['name']);

        Livewire::test(PermissionModal::class)
            ->call('delete', 'dashboard.view');

        $this->assertDatabaseHas('permissions', ['name' => 'dashboard.view']);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('Super Admin');

        return $user;
    }

    private function tenantUser(string $role): User
    {
        $tenant = Tenant::create([
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
            'email' => fake()->unique()->safeEmail(),
            'status' => 'active',
            'timezone' => 'Asia/Colombo',
            'currency' => 'LKR',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);
        $user->assignRole($role);

        return $user;
    }
}
