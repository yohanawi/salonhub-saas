<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProfilePagesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_profile_pages_render_for_authenticated_user(): void
    {
        $tenant = Tenant::create([
            'name' => 'Glow Studio',
            'slug' => 'glow-studio',
            'email' => 'owner@example.com',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+94771234567',
            'profile_photo_path' => null,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Jane Doe')
            ->assertSee('Overview');

        $this->actingAs($user)
            ->get(route('profile.settings'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('profile.logs'))
            ->assertOk();
    }
}
