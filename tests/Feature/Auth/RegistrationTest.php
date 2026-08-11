<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register()
    {
        Notification::fake();

        $response = $this->post('/register', [
            'business_name' => 'Glow Studio',
            'first_name' => 'Test',
            'last_name' => 'Owner',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'toc' => '1',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice'));

        $this->assertDatabaseHas('tenants', [
            'name' => 'Glow Studio',
            'slug' => 'glow-studio',
            'email' => 'test@example.com',
        ]);

        $this->assertDatabaseHas('users', [
            'first_name' => 'Test',
            'last_name' => 'Owner',
            'name' => 'Test Owner',
            'email' => 'test@example.com',
        ]);

        $user = User::where('email', 'test@example.com')->first();

        $this->assertTrue($user->hasRole('Salon Owner'));
        $this->assertSame(Tenant::where('slug', 'glow-studio')->value('id'), $user->tenant_id);
        $this->assertTrue(Role::where('name', 'Salon Owner')->exists());
    }

    public function test_new_users_can_register_with_ajax_request()
    {
        Notification::fake();

        $response = $this->postJson('/register', [
            'business_name' => 'Polish Lounge',
            'first_name' => 'Ajax',
            'last_name' => 'Owner',
            'email' => 'ajax@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'toc' => '1',
        ]);

        $this->assertAuthenticated();

        $response
            ->assertOk()
            ->assertJson([
                'redirect' => route('verification.notice'),
            ]);
    }
}
