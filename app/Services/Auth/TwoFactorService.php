<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Settings\SettingService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Authenticator-app (TOTP) two-factor sign-in: setup with a QR code, code checks, and one-time recovery codes.
 */
class TwoFactorService
{
    public const RECOVERY_CODE_COUNT = 8;

    public const SESSION_PENDING = 'two_factor.pending';

    public function __construct(private readonly Google2FA $google2fa)
    {
    }

    /**
     * Starts setup with a fresh secret. Two-factor stays off until a code from the app confirms it.
     */
    public function begin(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => $this->google2fa->generateSecretKey(32),
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_timestep' => null,
        ])->save();
    }

    /**
     * @return array<int, string>|null The recovery codes to show once, or null when the code was wrong.
     */
    public function confirm(User $user, string $code): ?array
    {
        if ($user->two_factor_secret === null || $user->hasTwoFactorEnabled() || ! $this->verifyCode($user, $code)) {
            return null;
        }

        $codes = $this->newRecoveryCodes();
        $user->forceFill(['two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => $codes])->save();

        return $codes;
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_timestep' => null,
        ])->save();
    }

    /**
     * @return array<int, string>
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->newRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();

        return $codes;
    }

    /**
     * Accepts a code from the app once; the same 30-second code cannot be used again.
     */
    public function verifyCode(User $user, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code);

        if ($user->two_factor_secret === null || ! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        // A window of 1 accepts the previous and next 30-second code too, for phone clocks that drift.
        $timestep = $this->google2fa->verifyKeyNewer($user->two_factor_secret, $code, (int) $user->two_factor_last_timestep, 1);

        if ($timestep === false) {
            return false;
        }

        $user->forceFill(['two_factor_last_timestep' => $timestep])->save();

        return true;
    }

    /**
     * A recovery code works once and is then removed.
     */
    public function useRecoveryCode(User $user, string $code): bool
    {
        $code = strtolower(trim($code));
        $codes = $user->two_factor_recovery_codes ?? [];

        foreach ($codes as $index => $stored) {
            if (hash_equals($stored, $code)) {
                unset($codes[$index]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }

    public function qrCodeSvg(User $user): string
    {
        $url = $this->google2fa->getQRCodeUrl(config('app.name'), $user->email, $user->two_factor_secret);

        return (new Writer(new ImageRenderer(new RendererStyle(192, 1), new SvgImageBackEnd())))->writeString($url);
    }

    /**
     * Whether the user's salon requires everyone to use two-factor sign-in.
     */
    public function requiredFor(User $user): bool
    {
        return $user->tenant_id !== null
            && (bool) app(SettingService::class)->get('security.two_factor_required', $user->tenant_id);
    }

    /**
     * @return array<int, string>
     */
    private function newRecoveryCodes(): array
    {
        return collect(range(1, self::RECOVERY_CODE_COUNT))
            ->map(fn () => strtolower(Str::random(5) . '-' . Str::random(5)))
            ->all();
    }
}
