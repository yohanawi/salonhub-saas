<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PlanEntitlementService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PlanManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);

        Role::firstOrCreate([
            'name' => 'Salon Owner',
            'guard_name' => 'web',
        ]);
    }

    public function test_super_admin_can_manage_subscription_plan_lifecycle(): void
    {
        $user = $this->superAdmin();

        $this->actingAs($user)
            ->get(route('plan-management.plans.index'))
            ->assertOk()
            ->assertSee('Subscription Plans');

        $this->actingAs($user)
            ->post(route('plan-management.plans.store'), [
                'name' => 'Growth',
                'slug' => 'growth',
                'price' => 9990,
                'billing_period' => 'monthly',
                'max_branches' => 5,
                'max_staff' => 30,
                'max_users' => 12,
                'max_customers' => 20000,
                'trial_days' => 0,
                'sort_order' => 50,
                'features' => ['services', 'appointment_calendar', 'pos_billing'],
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('plans', [
            'slug' => 'growth',
            'max_users' => 12,
            'max_customers' => 20000,
            'is_active' => true,
        ]);

        $plan = Plan::where('slug', 'growth')->firstOrFail();

        $this->assertTrue((bool) $plan->features['pos_billing']);

        $this->actingAs($user)
            ->put(route('plan-management.plans.update', $plan), [
                'name' => 'Growth Plus',
                'slug' => 'growth-plus',
                'price' => 10990,
                'billing_period' => 'monthly',
                'max_branches' => 6,
                'max_staff' => 35,
                'max_users' => 15,
                'max_customers' => 25000,
                'trial_days' => 0,
                'sort_order' => 55,
                'features' => ['services', 'inventory', 'advanced_reports', 'no_credit_card_required'],
                'is_active' => 1,
                'is_recommended' => 1,
            ])
            ->assertRedirect(route('plan-management.plans.show', $plan));

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'slug' => 'growth-plus',
            'is_recommended' => true,
        ]);

        $this->actingAs($user)
            ->patch(route('plan-management.plans.status.update', $plan), ['is_active' => 0])
            ->assertRedirect();

        $this->assertFalse($plan->fresh()->is_active);

        $this->actingAs($user)
            ->delete(route('plan-management.plans.destroy', $plan))
            ->assertRedirect(route('plan-management.plans.index'));

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'is_active' => false,
        ]);
    }

    public function test_non_super_admin_cannot_access_plan_management(): void
    {
        $owner = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $owner->assignRole('Salon Owner');

        $this->actingAs($owner)
            ->get(route('plan-management.plans.index'))
            ->assertForbidden();
    }

    public function test_subscription_assignment_stores_entitlement_snapshot(): void
    {
        $tenant = $this->tenant();
        $plan = $this->plan([
            'max_users' => 5,
            'features' => ['services' => true, 'inventory' => true],
        ]);

        $subscription = app(PlanEntitlementService::class)->assignPlan($tenant, $plan);

        $this->assertSame($plan->id, $subscription->plan_id);
        $this->assertSame(5, data_get($subscription->entitlements, 'limits.max_users'));
        $this->assertTrue(data_get($subscription->entitlements, 'features.inventory'));

        $plan->update([
            'max_users' => 1,
            'features' => ['services' => true, 'inventory' => false],
        ]);

        $subscription = $subscription->fresh();

        $this->assertSame(5, data_get($subscription->entitlements, 'limits.max_users'));
        $this->assertTrue(data_get($subscription->entitlements, 'features.inventory'));
    }

    public function test_super_admin_cannot_assign_plan_below_current_usage(): void
    {
        $superAdmin = $this->superAdmin();
        $tenant = $this->tenant();
        $plan = $this->plan(['max_staff' => 1]);

        Staff::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Asha',
            'last_name' => 'Perera',
            'status' => 'active',
        ]);

        Staff::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Nimal',
            'last_name' => 'Silva',
            'status' => 'active',
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('plan-management.subscriptions.update', $tenant), [
                'plan_id' => $plan->id,
                'status' => 'active',
            ])
            ->assertSessionHasErrors('max_staff');

        $this->assertDatabaseMissing('subscriptions', [
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);
    }

    public function test_super_admin_can_update_tenant_subscription(): void
    {
        $superAdmin = $this->superAdmin();
        $tenant = $this->tenant();
        $plan = $this->plan([
            'name' => 'Professional',
            'slug' => 'professional-test',
            'max_users' => 10,
            'features' => ['services' => true, 'advanced_reports' => true],
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('plan-management.subscriptions.update', $tenant), [
                'plan_id' => $plan->id,
                'status' => 'active',
            ])
            ->assertRedirect();

        $subscription = Subscription::where('tenant_id', $tenant->id)->firstOrFail();

        $this->assertSame($plan->id, $subscription->plan_id);
        $this->assertSame('active', $subscription->status);
        $this->assertTrue(data_get($subscription->entitlements, 'features.advanced_reports'));
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $user->assignRole('Super Admin');

        return $user;
    }

    private function tenant(): Tenant
    {
        return Tenant::create([
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
            'email' => fake()->unique()->safeEmail(),
            'status' => 'active',
            'timezone' => 'Asia/Colombo',
            'currency' => 'LKR',
        ]);
    }

    private function plan(array $overrides = []): Plan
    {
        return Plan::create(array_merge([
            'name' => fake()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'price' => 0,
            'billing_period' => 'trial',
            'max_branches' => 3,
            'max_staff' => 10,
            'max_users' => 5,
            'max_customers' => 100,
            'trial_days' => 14,
            'features' => ['services' => true, 'basic_reports' => true],
            'is_active' => true,
        ], $overrides));
    }
}
