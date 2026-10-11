<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class AuthenticationHardeningTest extends TestCase
{
    use DatabaseTransactions;

    public function test_deactivated_user_session_is_ended_on_next_request(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);

        $this->actingAs($user)->get(route('terms'))->assertOk();
        $this->assertAuthenticatedAs($user);

        $user->forceFill(['status' => 'inactive'])->save();

        $this->actingAs($user->fresh())
            ->get(route('terms'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_unknown_social_provider_is_not_found(): void
    {
        $this->get('/auth/redirect/github')->assertNotFound();
    }

    public function test_social_login_signs_in_existing_active_user(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com', 'status' => 'active']);
        $this->fakeSocialUser('owner@example.com', 'Salon Owner');

        $this->get('/auth/redirect/google?state=abc&code=xyz')->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_social_login_rejects_deactivated_user(): void
    {
        User::factory()->create(['email' => 'gone@example.com', 'status' => 'inactive']);
        $this->fakeSocialUser('gone@example.com', 'Gone User');

        $this->get('/auth/redirect/google?state=abc&code=xyz')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_social_login_with_unknown_email_is_sent_to_registration_without_creating_user(): void
    {
        $this->fakeSocialUser('new@example.com', 'Nimali Perera');

        $this->get('/auth/redirect/google?state=abc&code=xyz')
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('email')
            ->assertSessionHasInput('first_name', 'Nimali');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
    }

    private function fakeSocialUser(string $email, string $name): void
    {
        $socialUser = (new SocialiteUser())->map(['id' => '123', 'email' => $email, 'name' => $name]);

        Socialite::shouldReceive('driver->user')->andReturn($socialUser);
    }
}
