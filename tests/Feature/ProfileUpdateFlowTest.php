<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileUpdateFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_profile_details_update_persists(): void
    {
        $tenant = Tenant::create(['name' => 'Glow Studio', 'slug' => 'glow-studio-flow1', 'email' => 'f1@example.com']);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Old',
            'last_name' => 'Name',
            'phone' => '000',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->patch(route('profile.settings.update'), [
            'fname' => 'New',
            'lname' => 'Namer',
            'phone' => '+94770000000',
            'company' => 'Glow Studio Renamed',
            'website' => 'https://example.com',
            'country' => 'Sri Lanka',
            'currency' => 'USD',
            'timezone' => 'Asia/Colombo',
            'language' => 'en',
            'communication' => ['1'],
            'allow_marketing' => '1',
        ]);

        $response->assertRedirect(route('profile.settings'));
        $user->refresh();
        $tenant->refresh();

        $this->assertSame('New', $user->first_name);
        $this->assertSame('Namer', $user->last_name);
        $this->assertSame('+94770000000', $user->phone);
        $this->assertSame('Glow Studio Renamed', $tenant->name);
        $this->assertSame('https://example.com', $tenant->website);
        $this->assertSame('en', $tenant->settings['language'] ?? null);
        $this->assertTrue((bool) ($tenant->settings['marketing_opt_in'] ?? false));
    }

    public function test_password_update_requires_current_password_and_persists(): void
    {
        $tenant = Tenant::create(['name' => 'Glow Studio', 'slug' => 'glow-studio-flow2', 'email' => 'f2@example.com']);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'password' => Hash::make('old-password-123'),
            'email_verified_at' => now(),
        ]);

        $bad = $this->actingAs($user)->patch(route('profile.settings.password'), [
            'currentpassword' => 'wrong-password',
            'newpassword' => 'new-password-123',
            'newpassword_confirmation' => 'new-password-123',
        ]);
        $bad->assertSessionHasErrors('currentpassword');

        $good = $this->actingAs($user)->patch(route('profile.settings.password'), [
            'currentpassword' => 'old-password-123',
            'newpassword' => 'new-password-123',
            'newpassword_confirmation' => 'new-password-123',
        ]);
        $good->assertRedirect(route('profile.settings'));

        $this->assertTrue(Hash::check('new-password-123', $user->refresh()->password));
    }

    public function test_deactivate_logs_out_and_blocks_future_login(): void
    {
        $tenant = Tenant::create(['name' => 'Glow Studio', 'slug' => 'glow-studio-flow3', 'email' => 'f3@example.com']);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'deactivate-me@example.com',
            'password' => Hash::make('password-123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('profile.settings.deactivate'), [
            'deactivate' => '1',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertSame('inactive', $user->refresh()->status);

        $loginAttempt = $this->post(route('login'), [
            'email' => 'deactivate-me@example.com',
            'password' => 'password-123',
        ]);
        $loginAttempt->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
