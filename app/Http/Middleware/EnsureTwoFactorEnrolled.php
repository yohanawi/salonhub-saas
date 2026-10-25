<?php

namespace App\Http\Middleware;

use App\Services\Auth\TwoFactorService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * When a salon turns on "Two-Factor Authentication" in its security settings, its users must set it up
 * before they can use anything other than their account settings.
 */
class EnsureTwoFactorEnrolled
{
    private const ALLOWED_ROUTES = ['profile.settings', 'profile.settings.*', 'profile.two-factor.*', 'subscription.inactive'];

    public function __construct(private readonly TwoFactorService $twoFactor)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user
            || $user->hasTwoFactorEnabled()
            || $request->routeIs(...self::ALLOWED_ROUTES)
            || ! $this->twoFactor->requiredFor($user)) {
            return $next($request);
        }

        $message = 'Your salon requires two-factor authentication. Please turn it on to continue.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return redirect(route('profile.settings') . '#kt_account_settings_signin_method')->with('warning', $message);
    }
}
