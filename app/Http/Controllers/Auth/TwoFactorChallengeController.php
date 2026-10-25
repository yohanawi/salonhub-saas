<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Providers\RouteServiceProvider;
use App\Services\Audit\AuditLogService;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The second sign-in step for users with two-factor on. Until it is passed, EnsureTwoFactorChallengePassed
 * keeps every other page closed to them.
 */
class TwoFactorChallengeController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->session()->get(TwoFactorService::SESSION_PENDING)) {
            return redirect(RouteServiceProvider::HOME);
        }

        return view('pages/auth.two-factor-challenge');
    }

    public function store(Request $request, TwoFactorService $twoFactor, AuditLogService $audit): RedirectResponse
    {
        if (! $request->session()->get(TwoFactorService::SESSION_PENDING)) {
            return redirect(RouteServiceProvider::HOME);
        }

        $data = $request->validate([
            'code' => ['nullable', 'string', 'required_without:recovery_code'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $key = 'two-factor:' . $user->id;

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'code' => 'Too many attempts. Try again in ' . RateLimiter::availableIn($key) . ' seconds.',
            ]);
        }

        $passed = filled($data['code'] ?? null)
            ? $twoFactor->verifyCode($user, $data['code'])
            : $twoFactor->useRecoveryCode($user, $data['recovery_code']);

        if (! $passed) {
            RateLimiter::hit($key);
            $audit->recordAuthentication(AuditLog::ACTION_FAILED_LOGIN, $user, $request, ['reason' => 'two_factor_failed']);

            throw ValidationException::withMessages([
                filled($data['code'] ?? null) ? 'code' : 'recovery_code' => 'That code is not valid.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->forget(TwoFactorService::SESSION_PENDING);
        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME);
    }
}
