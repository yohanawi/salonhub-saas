<?php

namespace App\Http\Middleware;

use App\Services\Settings\SettingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs a salon user out after they have been idle longer than their salon's "Sign Out After Idle" setting
 * (Settings > Security), so a shared front-desk computer does not stay signed in. This also ends
 * "remember me" sessions once they go idle.
 */
class EnforceIdleTimeout
{
    private const SESSION_KEY = 'last_activity_at';

    public function __construct(private readonly SettingService $settings)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if (! $user?->tenant_id) {
            return $next($request);
        }

        $timeoutMinutes = (int) $this->settings->get('security.session_timeout_minutes', $user->tenant_id);
        $lastActivity = (int) $request->session()->get(self::SESSION_KEY, 0);

        if ($timeoutMinutes > 0 && $lastActivity > 0 && now()->getTimestamp() - $lastActivity > $timeoutMinutes * 60) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = 'You were signed out after ' . $timeoutMinutes . ' minutes without activity.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 401);
            }

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        $request->session()->put(self::SESSION_KEY, now()->getTimestamp());

        return $next($request);
    }
}
