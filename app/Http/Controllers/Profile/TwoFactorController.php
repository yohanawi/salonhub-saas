<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Turning authenticator-app sign-in on and off from the user's own account settings.
 */
class TwoFactorController extends Controller
{
    private const SECTION = 'kt_account_settings_signin_method';

    public function enable(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password']]);

        abort_if($request->user()->hasTwoFactorEnabled(), 422, 'Two-factor authentication is already on.');

        $twoFactor->begin($request->user());

        return $this->back('Scan the QR code with your authenticator app, then enter the 6-digit code.');
    }

    public function confirm(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string']]);

        $codes = $twoFactor->confirm($request->user(), $data['code']);

        if ($codes === null) {
            throw ValidationException::withMessages(['code' => 'That code is not valid. Check the time on your phone and try again.']);
        }

        return $this->back('Two-factor authentication is on.')->with('two_factor_recovery_codes', $codes);
    }

    public function recoveryCodes(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password']]);

        abort_unless($request->user()->hasTwoFactorEnabled(), 422, 'Two-factor authentication is off.');

        $codes = $twoFactor->regenerateRecoveryCodes($request->user());

        return $this->back('New recovery codes created. The old ones no longer work.')->with('two_factor_recovery_codes', $codes);
    }

    public function disable(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        // Cancelling an unfinished setup needs no password; turning off a working one does.
        if ($request->user()->hasTwoFactorEnabled()) {
            $request->validate(['current_password' => ['required', 'current_password']]);

            if ($twoFactor->requiredFor($request->user())) {
                throw ValidationException::withMessages(['current_password' => 'Your salon requires two-factor authentication, so it cannot be turned off.']);
            }
        }

        $twoFactor->disable($request->user());

        return $this->back('Two-factor authentication is off.');
    }

    private function back(string $status): RedirectResponse
    {
        return redirect(route('profile.settings') . '#' . self::SECTION)->with('status', $status);
    }
}
