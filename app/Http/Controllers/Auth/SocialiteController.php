<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class SocialiteController extends Controller
{
    private const PROVIDERS = ['google'];

    public function redirect(Request $request, string $provider, AuditLogService $auditLogService): RedirectResponse|SymfonyRedirectResponse
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        // First leg: send the user to the provider. The provider calls back to this same URL with a state/code.
        if (! $request->filled('state') && ! $request->filled('error')) {
            return Socialite::driver($provider)->redirect();
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('login')->withErrors(['email' => 'Social sign-in failed. Please try again.']);
        }

        $user = User::where('email', $socialUser->getEmail())->first();

        // Accounts belong to a salon (tenant), which only the registration form can create.
        if (! $user) {
            [$firstName, $lastName] = array_pad(explode(' ', (string) $socialUser->getName(), 2), 2, '');

            return redirect()->route('register')
                ->withInput(['email' => $socialUser->getEmail(), 'first_name' => $firstName, 'last_name' => $lastName])
                ->withErrors(['email' => 'No SaloonHub account uses this email yet. Please register your salon first.']);
        }

        if ($blockedReason = $user->loginBlockedReason()) {
            $auditLogService->recordAuthentication(AuditLog::ACTION_FAILED_LOGIN, $user, $request, [
                'email' => $user->email,
                'reason' => $user->status !== 'active' ? 'account_inactive' : 'tenant_suspended',
                'provider' => $provider,
            ]);

            return redirect()->route('login')->withErrors(['email' => $blockedReason]);
        }

        if (! $user->hasVerifiedEmail()) {
            // The provider has confirmed ownership of this address.
            $user->markEmailAsVerified();
        }

        auth()->login($user, true);
        $request->session()->regenerate();

        $user->update([
            'last_login_at' => Carbon::now()->toDateTimeString(),
            'last_login_ip' => $request->getClientIp(),
        ]);

        $auditLogService->recordAuthentication(AuditLog::ACTION_LOGIN, $user, $request, ['provider' => $provider]);

        return redirect()->intended(RouteServiceProvider::HOME);
    }
}
