<?php

namespace App\Http\Middleware;

use App\Services\PlanEntitlementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locks salon users out of the app while their salon's subscription is past due, cancelled or past its trial.
 * They can still reach their account pages and sign out; Super Admins are never blocked.
 */
class EnsureTenantSubscriptionIsActive
{
    public const ALLOWED_ROUTES = [
        'subscription.inactive',
        'profile.settings',
        'profile.settings.*',
        'profile.two-factor.*',
        'profile.security',
        'profile.billing',
        'profile.addresses.*',
    ];

    public function __construct(private readonly PlanEntitlementService $entitlements)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->tenant_id || $user->hasRole('Super Admin') || $request->routeIs(...self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        $reason = $user->tenant ? $this->entitlements->subscriptionBlockedReason($user->tenant) : null;

        if ($reason === null) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => $reason], 403);
        }

        return redirect()->route('subscription.inactive');
    }
}
