<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProfilePagesRealDataTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $slug, string $email): User
    {
        $tenant = Tenant::create([
            'name' => 'Glow Studio',
            'slug' => $slug,
            'email' => "owner-{$slug}@example.com",
            'country' => 'Sri Lanka',
            'timezone' => 'Asia/Colombo',
            'currency' => 'USD',
        ]);

        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => $email,
            'phone' => '+94771234567',
            'email_verified_at' => now(),
        ]);
    }

    public function test_my_profile_shows_real_stats_and_no_recent_activity_empty_state(): void
    {
        $user = $this->makeUser('glow-real-1', 'jane-real-1@example.com');

        $this->actingAs($user)->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Recent Activity')
            ->assertSee('Branches')
            ->assertSee('No recent activity yet.');
    }

    public function test_settings_page_prefills_real_user_and_tenant_data(): void
    {
        $user = $this->makeUser('glow-real-2', 'jane-real-2@example.com');

        $this->actingAs($user)->get(route('profile.settings'))
            ->assertOk()
            ->assertSee('Jane', false)
            ->assertSee('Sri Lanka', false);
    }

    public function test_security_page_shows_empty_state_with_no_fake_numbers(): void
    {
        $user = $this->makeUser('glow-real-3', 'jane-real-3@example.com');

        $this->actingAs($user)->get(route('profile.security'))
            ->assertOk()
            ->assertSee('No security events recorded yet.')
            ->assertDontSee('36899');
    }

    public function test_activity_page_shows_empty_state_with_no_fake_projects(): void
    {
        $user = $this->makeUser('glow-real-4', 'jane-real-4@example.com');

        $this->actingAs($user)->get(route('profile.activity'))
            ->assertOk()
            ->assertSee('No activity recorded for this period.')
            ->assertDontSee('AirPlus Mobile App');
    }

    public function test_billing_page_shows_honest_empty_states(): void
    {
        $user = $this->makeUser('glow-real-5', 'jane-real-5@example.com');

        $this->actingAs($user)->get(route('profile.billing'))
            ->assertOk()
            ->assertSee('No active subscription')
            ->assertSee('No payment method on file')
            ->assertSee('No billing history yet.')
            ->assertDontSee('Marcus Morris');
    }

    public function test_statements_page_shows_empty_state_with_no_fake_products(): void
    {
        $user = $this->makeUser('glow-real-6', 'jane-real-6@example.com');

        $this->actingAs($user)->get(route('profile.statements'))
            ->assertOk()
            ->assertSee('No statements available yet.')
            ->assertDontSee('Icons Pack');
    }

    public function test_logs_page_shows_empty_states_and_export_works(): void
    {
        $user = $this->makeUser('glow-real-7', 'jane-real-7@example.com');

        $this->actingAs($user)->get(route('profile.logs'))
            ->assertOk()
            ->assertSee('No login sessions in this window.')
            ->assertSee('No logs recorded yet.')
            ->assertDontSee('236.125.56.78');

        $this->actingAs($user)->get(route('profile.logs.export'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=utf-8');
    }
}
