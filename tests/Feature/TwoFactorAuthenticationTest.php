<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Auth\TwoFactorService;
use App\Services\Settings\SettingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PragmaRX\Google2FA\Google2FA;
use Tests\Feature\Concerns\BuildsSalonFixtures;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use BuildsSalonFixtures;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_user_turns_on_two_factor_with_a_code_from_their_app(): void
    {
        [, $owner] = $this->makeSalon();
        $owner->forceFill(['password' => bcrypt('secret-password')])->save();

        $this->actingAs($owner)
            ->post(route('profile.two-factor.enable'), ['current_password' => 'wrong'])
            ->assertSessionHasErrors('current_password');

        $this->actingAs($owner)
            ->post(route('profile.two-factor.enable'), ['current_password' => 'secret-password'])
            ->assertRedirect();

        $owner->refresh();
        $this->assertNotNull($owner->two_factor_secret);
        $this->assertFalse($owner->hasTwoFactorEnabled());

        $this->actingAs($owner)
            ->get(route('profile.settings'))
            ->assertOk()
            ->assertSee('<svg', false)
            ->assertSee('Confirm & turn on', false);

        $this->actingAs($owner)
            ->post(route('profile.two-factor.confirm'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->actingAs($owner)
            ->post(route('profile.two-factor.confirm'), ['code' => $this->currentCode($owner)])
            ->assertSessionHas('two_factor_recovery_codes', fn ($codes) => count($codes) === TwoFactorService::RECOVERY_CODE_COUNT);

        $this->assertTrue($owner->fresh()->hasTwoFactorEnabled());
    }

    public function test_sign_in_stops_at_the_challenge_until_a_valid_code_is_entered(): void
    {
        [, $owner] = $this->makeSalon();
        $this->enableTwoFactor($owner);

        $this->post(route('login'), ['email' => $owner->email, 'password' => 'secret-password']);
        $this->assertAuthenticatedAs($owner);

        $this->get(route('dashboard'))->assertRedirect(route('two-factor.challenge'));
        $this->getJson(route('dashboard'))->assertStatus(423);
        $this->get(route('two-factor.challenge'))->assertOk()->assertSee('Two-step verification');

        $this->post(route('two-factor.challenge.store'), ['code' => '123456'])->assertSessionHasErrors('code');
        $this->get(route('dashboard'))->assertRedirect(route('two-factor.challenge'));

        $code = $this->currentCode($owner);
        $this->post(route('two-factor.challenge.store'), ['code' => $code])->assertRedirect();
        $this->get(route('dashboard'))->assertOk();

        // The same code cannot be used for a second sign-in.
        $this->post(route('logout'));
        $this->post(route('login'), ['email' => $owner->email, 'password' => 'secret-password']);
        $this->post(route('two-factor.challenge.store'), ['code' => $code])->assertSessionHasErrors('code');
    }

    public function test_recovery_code_signs_in_once(): void
    {
        [, $owner] = $this->makeSalon();
        $codes = $this->enableTwoFactor($owner);

        $this->post(route('login'), ['email' => $owner->email, 'password' => 'secret-password']);
        $this->post(route('two-factor.challenge.store'), ['recovery_code' => strtoupper($codes[0])])->assertRedirect();
        $this->get(route('dashboard'))->assertOk();
        $this->assertCount(TwoFactorService::RECOVERY_CODE_COUNT - 1, $owner->fresh()->two_factor_recovery_codes);

        $this->post(route('logout'));
        $this->post(route('login'), ['email' => $owner->email, 'password' => 'secret-password']);
        $this->post(route('two-factor.challenge.store'), ['recovery_code' => $codes[0]])->assertSessionHasErrors('recovery_code');
    }

    public function test_challenge_is_rate_limited(): void
    {
        [, $owner] = $this->makeSalon();
        $this->enableTwoFactor($owner);
        $this->post(route('login'), ['email' => $owner->email, 'password' => 'secret-password']);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('two-factor.challenge.store'), ['code' => '000000']);
        }

        $this->post(route('two-factor.challenge.store'), ['code' => $this->currentCode($owner)])
            ->assertSessionHasErrors('code');

        $this->assertStringContainsString('Too many attempts', session('errors')->first('code'));
        $this->get(route('dashboard'))->assertRedirect(route('two-factor.challenge'));
    }

    public function test_salon_can_require_two_factor_for_everyone(): void
    {
        [$tenant, $owner] = $this->makeSalon();
        app(SettingService::class)->setMany($tenant, null, ['security.two_factor_required' => true], $owner);

        $this->actingAs($owner)
            ->get(route('customer-management.customers.index'))
            ->assertRedirect(route('profile.settings') . '#kt_account_settings_signin_method');

        $this->actingAs($owner)->get(route('profile.settings'))->assertOk()->assertSee('Your salon requires this.');

        $this->enableTwoFactor($owner);
        $this->actingAs($owner)->get(route('customer-management.customers.index'))->assertOk();

        // It cannot be turned off while the salon requires it.
        $this->actingAs($owner)
            ->delete(route('profile.two-factor.disable'), ['current_password' => 'secret-password'])
            ->assertSessionHasErrors('current_password');
        $this->assertTrue($owner->fresh()->hasTwoFactorEnabled());
    }

    public function test_user_turns_off_two_factor_with_their_password(): void
    {
        [, $owner] = $this->makeSalon();
        $this->enableTwoFactor($owner);

        $this->actingAs($owner)
            ->delete(route('profile.two-factor.disable'), ['current_password' => 'wrong'])
            ->assertSessionHasErrors('current_password');

        $this->actingAs($owner)
            ->delete(route('profile.two-factor.disable'), ['current_password' => 'secret-password'])
            ->assertSessionHas('status', 'Two-factor authentication is off.');

        $this->assertFalse($owner->fresh()->hasTwoFactorEnabled());
        $this->assertNull($owner->fresh()->two_factor_secret);
    }

    /**
     * @return array<int, string> recovery codes
     */
    private function enableTwoFactor(User $user): array
    {
        $user->forceFill(['password' => bcrypt('secret-password')])->save();
        $twoFactor = app(TwoFactorService::class);
        $twoFactor->begin($user);

        $google2fa = app(Google2FA::class);
        // Confirm with the previous window's code so the current one is still unused for the sign-in tests.
        $codes = $twoFactor->confirm($user, $google2fa->oathTotp($user->two_factor_secret, $google2fa->getTimestamp() - 1));
        $this->assertNotNull($codes);

        return $codes;
    }

    private function currentCode(User $user): string
    {
        return app(Google2FA::class)->getCurrentOtp($user->fresh()->two_factor_secret);
    }
}
