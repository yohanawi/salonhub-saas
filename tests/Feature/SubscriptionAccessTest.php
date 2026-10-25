<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PlanEntitlementService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\BuildsSalonFixtures;
use Tests\TestCase;

class SubscriptionAccessTest extends TestCase
{
    use BuildsSalonFixtures;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_active_and_running_trial_subscriptions_keep_full_access(): void
    {
        [$tenant, $owner] = $this->makeSalon();

        $this->actingAs($owner)->get(route('customer-management.customers.index'))->assertOk();

        $this->subscriptionOf($tenant)->update(['status' => Subscription::STATUS_ACTIVE, 'trial_ends_at' => null]);

        $this->actingAs($owner)->get(route('customer-management.customers.index'))->assertOk();
        $this->assertTrue(app(PlanEntitlementService::class)->featureEnabled($tenant, 'pos_billing'));
    }

    public function test_cancelled_subscription_locks_the_salon_out(): void
    {
        [$tenant, $owner] = $this->makeSalon();
        $this->subscriptionOf($tenant)->update([
            'status' => Subscription::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'ends_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('customer-management.customers.index'))
            ->assertRedirect(route('subscription.inactive'));

        $this->actingAs($owner)
            ->get(route('subscription.inactive'))
            ->assertOk()
            ->assertSee('Your subscription has been cancelled.');

        $this->assertFalse(app(PlanEntitlementService::class)->featureEnabled($tenant, 'pos_billing'));
    }

    public function test_past_due_subscription_locks_the_salon_out(): void
    {
        [$tenant, $owner] = $this->makeSalon();
        $this->subscriptionOf($tenant)->update(['status' => Subscription::STATUS_PAST_DUE]);

        $this->actingAs($owner)->get(route('dashboard'))->assertRedirect(route('subscription.inactive'));
        $this->actingAs($owner)
            ->postJson(route('customer-management.customers.store'), [])
            ->assertForbidden()
            ->assertJsonPath('message', 'Your subscription payment is overdue. Please contact support to restore access.');
    }

    public function test_expired_trial_locks_the_salon_out(): void
    {
        [$tenant, $owner] = $this->makeSalon();
        $this->subscriptionOf($tenant)->update(['trial_ends_at' => now()->subMinute()]);

        $this->actingAs($owner)
            ->get(route('customer-management.customers.index'))
            ->assertRedirect(route('subscription.inactive'));

        $this->expectException(ValidationException::class);
        app(PlanEntitlementService::class)->ensureCanCreate($tenant, 'max_staff');
    }

    public function test_locked_out_users_can_still_reach_their_account_pages(): void
    {
        [$tenant, $owner] = $this->makeSalon();
        $this->subscriptionOf($tenant)->update(['status' => Subscription::STATUS_PAST_DUE]);

        $this->actingAs($owner)->get(route('profile.settings'))->assertOk();
        $this->actingAs($owner)->get(route('profile.billing'))->assertOk();
    }

    public function test_inactive_page_sends_users_with_access_back_to_the_dashboard(): void
    {
        [, $owner] = $this->makeSalon();

        $this->actingAs($owner)->get(route('subscription.inactive'))->assertRedirect(route('dashboard'));
    }

    public function test_reactivating_the_subscription_restores_access(): void
    {
        [$tenant, $owner] = $this->makeSalon();
        $subscription = $this->subscriptionOf($tenant);
        $subscription->update(['status' => Subscription::STATUS_CANCELLED, 'cancelled_at' => now(), 'ends_at' => now()]);

        app(PlanEntitlementService::class)->assignPlan($tenant, $subscription->plan, Subscription::STATUS_ACTIVE);

        $this->actingAs($owner)->get(route('customer-management.customers.index'))->assertOk();
    }

    public function test_super_admin_is_never_locked_out(): void
    {
        [$tenant] = $this->makeSalon();
        $this->subscriptionOf($tenant)->update(['status' => Subscription::STATUS_CANCELLED]);

        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'email_verified_at' => now()]);
        $admin->assignRole(Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']));

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    }

    public function test_scheduled_jobs_only_see_salons_with_access(): void
    {
        [$active] = $this->makeSalon();
        [$cancelled] = $this->makeSalon();
        [$expiredTrial] = $this->makeSalon();

        $this->subscriptionOf($cancelled)->update(['status' => Subscription::STATUS_CANCELLED, 'ends_at' => now()]);
        $this->subscriptionOf($expiredTrial)->update(['trial_ends_at' => now()->subDay()]);

        $ids = Tenant::query()
            ->operational()
            ->whereKey([$active->id, $cancelled->id, $expiredTrial->id])
            ->pluck('id')
            ->all();

        $this->assertSame([$active->id], $ids);
    }

    private function subscriptionOf(Tenant $tenant): Subscription
    {
        return Subscription::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();
    }
}
