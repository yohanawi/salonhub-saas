<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchBusinessHour;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DashboardOnboardingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_dashboard_shows_onboarding_modal_for_new_verified_user(): void
    {
        $plan = $this->createTrialPlan();
        $tenant = Tenant::create([
            'name' => 'Glow Studio',
            'slug' => 'glow-studio-test',
            'email' => 'owner@example.com',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
            'onboarded_at' => null,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Set up your salon')
            ->assertSee('onboarding-submit')
            ->assertSee('Add another service')
            ->assertSee('add-first-staff')
            ->assertSee($plan->name);
    }

    public function test_user_can_complete_onboarding(): void
    {
        $plan = $this->createTrialPlan();
        $tenant = Tenant::create([
            'name' => 'Polish Lounge',
            'slug' => 'polish-lounge-test',
            'email' => 'owner2@example.com',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
            'onboarded_at' => null,
        ]);

        $this->actingAs($user)
            ->post('/onboarding/complete', $this->payload())
            ->assertRedirect();

        $this->assertNotNull($user->fresh()->onboarded_at);
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'phone' => '+94771234567',
            'country' => 'Sri Lanka',
            'currency' => 'LKR',
            'timezone' => 'Asia/Colombo',
            'business_type' => 'Beauty Salon',
        ]);
        $this->assertDatabaseHas('branches', [
            'tenant_id' => $tenant->id,
            'code' => 'MAIN',
            'name' => 'Main Branch',
            'postal_code' => '00100',
        ]);
        $this->assertSame(7, BranchBusinessHour::where('tenant_id', $tenant->id)->count());
        $this->assertSame(1, BranchBusinessHour::where('tenant_id', $tenant->id)->where('day_of_week', 7)->where('is_closed', true)->count());
        $this->assertSame(2, Service::where('tenant_id', $tenant->id)->count());
        $this->assertSame(1, Staff::where('tenant_id', $tenant->id)->count());
        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'trialing',
        ]);
        $this->assertNotNull(Subscription::where('tenant_id', $tenant->id)->first()?->trial_ends_at);

        $this->actingAs($user->fresh())
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Set up your salon');
    }

    private function payload(): array
    {
        return [
            'business_phone' => '+94771234567',
            'country' => 'Sri Lanka',
            'currency' => 'LKR',
            'timezone' => 'Asia/Colombo',
            'business_type' => 'Beauty Salon',
            'plan_id' => Plan::where('slug', 'free-trial')->value('id'),

            'branch_name' => 'Main Branch',
            'branch_phone' => '+94777654321',
            'branch_email' => 'branch@example.com',
            'branch_address' => 'No. 10, Main Street',
            'branch_city' => 'Colombo',
            'branch_postal_code' => '00100',

            'hours' => [
                ['day_of_week' => 1, 'opens_at' => '09:00', 'closes_at' => '19:00'],
                ['day_of_week' => 2, 'opens_at' => '09:00', 'closes_at' => '19:00'],
                ['day_of_week' => 3, 'opens_at' => '09:00', 'closes_at' => '19:00'],
                ['day_of_week' => 4, 'opens_at' => '09:00', 'closes_at' => '19:00'],
                ['day_of_week' => 5, 'opens_at' => '09:00', 'closes_at' => '19:00'],
                ['day_of_week' => 6, 'opens_at' => '09:00', 'closes_at' => '20:00'],
                ['day_of_week' => 7, 'is_closed' => 1],
            ],

            'services' => [
                ['name' => 'Hair Cut', 'category' => 'Hair', 'duration_minutes' => 45, 'price' => 2500],
                ['name' => 'Facial', 'category' => 'Beauty', 'duration_minutes' => 60, 'price' => 4500],
            ],

            'staff_first_name' => 'Nimali',
            'staff_last_name' => 'Perera',
            'staff_phone' => '+94770000000',
            'staff_job_title' => 'Senior Beautician',
        ];
    }

    private function createTrialPlan(): Plan
    {
        return Plan::updateOrCreate(
            ['slug' => 'free-trial'],
            [
                'name' => 'Free Trial',
                'price' => 0,
                'billing_period' => 'trial',
                'max_branches' => 1,
                'max_staff' => 5,
                'max_users' => 3,
                'max_customers' => 10000,
                'trial_days' => 14,
                'features' => ['services' => true],
                'is_active' => true,
                'is_recommended' => false,
                'sort_order' => 10,
            ]
        );
    }
}
