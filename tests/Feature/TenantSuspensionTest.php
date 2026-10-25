<?php

namespace Tests\Feature;

use App\Livewire\User\AddUserModal;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Feature\Concerns\BuildsSalonFixtures;
use Tests\TestCase;

class TenantSuspensionTest extends TestCase
{
    use BuildsSalonFixtures;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_super_admin_can_suspend_and_reactivate_a_salon(): void
    {
        [$tenant] = $this->makeSalon();
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->patch(route('plan-management.tenants.status.update', $tenant), ['status' => Tenant::STATUS_SUSPENDED])
            ->assertSessionHas('status', "{$tenant->name} has been suspended.");
        $this->assertTrue($tenant->fresh()->isSuspended());

        $this->actingAs($admin)
            ->patch(route('plan-management.tenants.status.update', $tenant), ['status' => Tenant::STATUS_ACTIVE])
            ->assertSessionHas('status', "{$tenant->name} has been reactivated.");
        $this->assertFalse($tenant->fresh()->isSuspended());
    }

    public function test_salon_users_cannot_suspend_salons(): void
    {
        [$tenant, $owner] = $this->makeSalon();

        $this->actingAs($owner)
            ->patch(route('plan-management.tenants.status.update', $tenant), ['status' => Tenant::STATUS_SUSPENDED])
            ->assertForbidden();
    }

    public function test_suspended_salon_users_are_signed_out_and_cannot_sign_in(): void
    {
        [$tenant, $owner] = $this->makeSalon();
        $owner->forceFill(['password' => bcrypt('secret-password')])->save();
        $tenant->update(['status' => Tenant::STATUS_SUSPENDED]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'This salon account has been suspended. Please contact support.']);
        $this->assertGuest();

        $this->post(route('login'), ['email' => $owner->email, 'password' => 'secret-password'])
            ->assertSessionHasErrors(['email' => 'This salon account has been suspended. Please contact support.']);
        $this->assertGuest();
    }

    public function test_scheduled_jobs_skip_suspended_salons(): void
    {
        [$active] = $this->makeSalon();
        [$suspended] = $this->makeSalon();
        $suspended->update(['status' => Tenant::STATUS_SUSPENDED]);

        $ids = Tenant::query()->operational()->whereKey([$active->id, $suspended->id])->pluck('id')->all();

        $this->assertSame([$active->id], $ids);
    }

    public function test_adding_a_user_to_a_salon_respects_the_plan_user_limit(): void
    {
        Notification::fake();
        [$tenant] = $this->makeSalon(); // plan allows 10 users; the owner takes one
        Role::firstOrCreate(['name' => 'Receptionist', 'guard_name' => 'web']);
        User::factory()->count(9)->create(['tenant_id' => $tenant->id]);

        $this->actingAs($this->superAdmin());

        Livewire::test(AddUserModal::class)
            ->set('name', 'Extra Person')
            ->set('email', 'extra.person@example.com')
            ->set('role', 'Receptionist')
            ->set('tenant_id', $tenant->id)
            ->call('submit')
            ->assertHasErrors(['tenant_id']);

        $this->assertDatabaseMissing('users', ['email' => 'extra.person@example.com']);
    }

    public function test_adding_a_user_to_a_salon_with_free_seats_assigns_the_salon(): void
    {
        Notification::fake();
        [$tenant] = $this->makeSalon();
        Role::firstOrCreate(['name' => 'Receptionist', 'guard_name' => 'web']);

        $this->actingAs($this->superAdmin());

        Livewire::test(AddUserModal::class)
            ->set('name', 'New Receptionist')
            ->set('email', 'new.receptionist@example.com')
            ->set('role', 'Receptionist')
            ->set('tenant_id', $tenant->id)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'new.receptionist@example.com', 'tenant_id' => $tenant->id]);
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create(['tenant_id' => null, 'email_verified_at' => now()]);
        $admin->assignRole(Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']));

        return $admin;
    }
}
