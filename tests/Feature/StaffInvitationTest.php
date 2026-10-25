<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\StaffInvitationNotification;
use App\Services\Staff\StaffAccessService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Concerns\BuildsSalonFixtures;
use Tests\TestCase;

class StaffInvitationTest extends TestCase
{
    use BuildsSalonFixtures;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        Notification::fake();

        foreach (['Salon Admin', 'Branch Manager', 'Receptionist', 'Cashier', 'Beautician'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    public function test_owner_invites_a_staff_member_who_sets_a_password_and_signs_in(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        $staff = $this->makeStaff($tenant, $branch);

        $this->actingAs($owner)
            ->get(route('staff-management.staff.show', $staff))
            ->assertOk()
            ->assertSee('Send Invitation');

        $this->actingAs($owner)
            ->post(route('staff-management.staff.access.invite', $staff), ['email' => 'Nadeesha@Example.com', 'role' => 'Receptionist'])
            ->assertSessionHas('status', 'Invitation sent to nadeesha@example.com.');

        $user = User::query()->where('email', 'nadeesha@example.com')->firstOrFail();
        $this->assertSame($tenant->id, (int) $user->tenant_id);
        $this->assertTrue($user->hasRole('Receptionist'));
        $this->assertSame($user->id, (int) $staff->fresh()->user_id);
        $this->assertTrue($user->branches()->whereKey($branch->id)->exists());
        $this->assertTrue($user->hasPendingInvitation());

        $url = null;
        Notification::assertSentTo($user, StaffInvitationNotification::class, function ($notification) use (&$url) {
            $url = $notification->url;

            return true;
        });

        auth()->logout();

        $this->get($url)->assertOk()->assertSee('Choose a password');

        $this->post($url, ['password' => 'Str0ng-Passw0rd!', 'password_confirmation' => 'Str0ng-Passw0rd!'])
            ->assertRedirect();

        $this->assertAuthenticatedAs($user->fresh());
        $user->refresh();
        $this->assertNotNull($user->invitation_accepted_at);
        $this->assertNotNull($user->email_verified_at);

        // The link only works once.
        auth()->logout();
        $this->get($url)->assertOk()->assertSee('Invitation expired');
        $this->post($url, ['password' => 'Other-Passw0rd!', 'password_confirmation' => 'Other-Passw0rd!'])->assertForbidden();
    }

    public function test_resending_invalidates_the_earlier_link(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        $staff = $this->makeStaff($tenant, $branch);
        $access = app(StaffAccessService::class);
        $user = $access->invite($staff, 'first@example.com', 'Beautician', $owner);
        $firstUrl = $access->invitationUrl($user);

        $this->travel(5)->seconds();
        $this->actingAs($owner)
            ->post(route('staff-management.staff.access.resend', $staff))
            ->assertSessionHas('status');

        auth()->logout();
        $this->get($firstUrl)->assertSee('Invitation expired');
        $this->get($access->invitationUrl($user->fresh()))->assertSee('Choose a password');
    }

    public function test_expired_invitation_shows_the_expired_page(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        Subscription::withoutTenantScope()->where('tenant_id', $tenant->id)->update(['status' => Subscription::STATUS_ACTIVE, 'trial_ends_at' => null]);
        $access = app(StaffAccessService::class);
        $user = $access->invite($this->makeStaff($tenant, $branch), 'late@example.com', 'Cashier', $owner);
        $url = $access->invitationUrl($user);

        $this->travel(StaffAccessService::INVITATION_DAYS + 1)->days();
        auth()->logout();

        $this->get($url)->assertOk()->assertSee('Invitation expired');
    }

    public function test_invite_respects_the_plan_user_limit(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon(); // plan allows 10 users; the owner takes one
        User::factory()->count(9)->create(['tenant_id' => $tenant->id]);
        $staff = $this->makeStaff($tenant, $branch);

        $this->actingAs($owner)
            ->post(route('staff-management.staff.access.invite', $staff), ['email' => 'extra@example.com', 'role' => 'Receptionist'])
            ->assertSessionHasErrors(['email' => 'Your subscription user limit has been reached.']);

        $this->assertDatabaseMissing('users', ['email' => 'extra@example.com']);
        $this->assertNull($staff->fresh()->user_id);
        Notification::assertNothingSent();
    }

    public function test_invite_rejects_taken_emails_and_roles_the_inviter_cannot_give(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        $staff = $this->makeStaff($tenant, $branch);
        $existing = User::factory()->create();

        $this->actingAs($owner)
            ->post(route('staff-management.staff.access.invite', $staff), ['email' => $existing->email, 'role' => 'Receptionist'])
            ->assertSessionHasErrors('email');

        $this->actingAs($owner)
            ->post(route('staff-management.staff.access.invite', $staff), ['email' => 'boss@example.com', 'role' => 'Salon Owner'])
            ->assertSessionHasErrors('role');

        // A Salon Admin may not create another Salon Admin.
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'email_verified_at' => now()]);
        $admin->assignRole(Role::findByName('Salon Admin')->givePermissionTo(['staff.view', 'staff.update', 'staff.manage_access']));

        $this->actingAs($admin)
            ->post(route('staff-management.staff.access.invite', $staff), ['email' => 'admin2@example.com', 'role' => 'Salon Admin'])
            ->assertSessionHasErrors('role');
    }

    public function test_owner_changes_role_and_turns_login_off_and_on(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        $staff = $this->makeStaff($tenant, $branch);
        $user = app(StaffAccessService::class)->invite($staff, 'cashier@example.com', 'Cashier', $owner);

        $this->actingAs($owner)
            ->patch(route('staff-management.staff.access.role', $staff), ['role' => 'Branch Manager'])
            ->assertSessionHas('status', 'Login role changed to Branch Manager.');
        $this->assertTrue($user->fresh()->hasRole('Branch Manager'));
        $this->assertFalse($user->fresh()->hasRole('Cashier'));

        $this->actingAs($owner)->patch(route('staff-management.staff.access.status', $staff), ['enabled' => 0]);
        $this->assertSame('inactive', $user->fresh()->status);

        $this->actingAs($owner)->patch(route('staff-management.staff.access.status', $staff), ['enabled' => 1]);
        $this->assertSame('active', $user->fresh()->status);
    }

    public function test_owner_login_cannot_be_changed_and_staff_without_permission_cannot_invite(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        $ownerStaff = $this->makeStaff($tenant, $branch, $owner);

        $this->actingAs($owner)
            ->patch(route('staff-management.staff.access.status', $ownerStaff), ['enabled' => 0])
            ->assertSessionHasErrors('access');
        $this->assertSame('active', $owner->fresh()->status);

        $receptionist = User::factory()->create(['tenant_id' => $tenant->id, 'email_verified_at' => now()]);
        $receptionist->assignRole(Role::findByName('Receptionist')->givePermissionTo(['staff.view']));

        $this->actingAs($receptionist)
            ->post(route('staff-management.staff.access.invite', $this->makeStaff($tenant, $branch)), ['email' => 'x@example.com', 'role' => 'Cashier'])
            ->assertForbidden();
    }
}
