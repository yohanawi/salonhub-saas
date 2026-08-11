<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PlanManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_super_admin_can_view_and_create_subscription_plans(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);

        $user->assignRole('Super Admin');

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
            ->assertRedirect(route('plan-management.plans.index'));

        $this->assertDatabaseHas('plans', [
            'slug' => 'growth',
            'max_users' => 12,
            'max_customers' => 20000,
            'is_active' => true,
        ]);

        $this->assertTrue((bool) Plan::where('slug', 'growth')->first()?->features['pos_billing']);
    }
}
