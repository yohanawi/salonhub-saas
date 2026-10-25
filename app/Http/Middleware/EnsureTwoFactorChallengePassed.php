<?php

namespace App\Http\Middleware;

use App\Services\Auth\TwoFactorService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps a user who signed in with their password, but has not yet entered their two-factor code, on the
 * challenge page. Runs on every web request, so Livewire and AJAX calls are covered too.
 */
class EnsureTwoFactorChallengePassed
{
    private const ALLOWED_ROUTES = ['two-factor.challenge', 'two-factor.challenge.store', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('web')->check()
            || ! $request->session()->get(TwoFactorService::SESSION_PENDING)
            || $request->routeIs(...self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Two-factor authentication required.', 'redirect' => route('two-factor.challenge')], 423);
        }

        return redirect()->route('two-factor.challenge');
    }
}
