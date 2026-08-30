<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\DeactivateAccountRequest;
use App\Http\Requests\Profile\UpdateProfileDetailsRequest;
use App\Http\Requests\Profile\UpdateProfileEmailRequest;
use App\Http\Requests\Profile\UpdateProfilePasswordRequest;
use App\Models\AuditLog;
use App\Models\Subscription;
use App\Services\Audit\AuditLogService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        return view('pages.apps.profile.my-profile', [
            'user' => $user,
            'tenant' => $user->tenant,
            'recentActivity' => AuditLog::where('user_id', $user->id)
                ->latest('created_at')
                ->limit(5)
                ->get(),
        ]);
    }

    public function settingsEdit(Request $request): View
    {
        $user = $request->user();
        $tenant = $user->tenant;

        return view('pages.apps.profile.settings', [
            'user' => $user,
            'tenant' => $tenant,
            'language' => $tenant?->settings['language'] ?? null,
            'communicationChannels' => $tenant?->settings['communication_channels'] ?? [],
            'marketingOptIn' => (bool) ($tenant?->settings['marketing_opt_in'] ?? false),
        ]);
    }

    public function updateDetails(UpdateProfileDetailsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $tenant = $user->tenant;

        $user->fill(array_filter([
            'first_name' => $request->input('fname'),
            'last_name' => $request->input('lname'),
            'phone' => $request->filled('phone') ? $request->input('phone') : null,
        ], fn ($value) => $value !== null));

        if ($request->boolean('avatar_remove')) {
            $user->profile_photo_path = null;
        }

        if ($request->hasFile('avatar')) {
            $user->profile_photo_path = $request->file('avatar')->store('avatars', 'public');
        }

        $user->save();

        if ($tenant) {
            $tenantUpdates = array_filter([
                'name' => $request->filled('company') ? $request->input('company') : null,
                'website' => $request->filled('website') ? $request->input('website') : null,
                'country' => $request->filled('country') ? $request->input('country') : null,
                'currency' => $request->filled('currency') ? $request->input('currency') : null,
            ], fn ($value) => $value !== null);

            $tenant->fill($tenantUpdates);

            $settings = $tenant->settings ?? [];

            if ($request->filled('language')) {
                $settings['language'] = $request->input('language');
            }

            $settings['communication_channels'] = $request->input('communication', []);
            $settings['marketing_opt_in'] = $request->boolean('allow_marketing');

            $tenant->settings = $settings;
            $tenant->save();
        }

        return redirect()->route('profile.settings')->with('status', 'Your profile has been updated.');
    }

    public function updateEmail(UpdateProfileEmailRequest $request, AuditLogService $auditLogService): RedirectResponse
    {
        $user = $request->user();
        $oldEmail = $user->email;

        $user->email = $request->input('emailaddress');
        $user->email_verified_at = null;
        $user->save();

        $auditLogService->log([
            'action' => 'user.email_updated',
            'event' => 'user.email_updated',
            'module' => 'users',
            'description' => 'Account email address updated.',
            'auditable' => $user,
            'old_values' => ['email' => $oldEmail],
            'new_values' => ['email' => $user->email],
        ], $request);

        return redirect()->route('profile.settings')->with('status', 'Your email address has been updated.');
    }

    public function updatePassword(UpdateProfilePasswordRequest $request, AuditLogService $auditLogService): RedirectResponse
    {
        $user = $request->user();
        $user->password = Hash::make($request->input('newpassword'));
        $user->save();

        $auditLogService->log([
            'action' => 'user.password_updated',
            'event' => 'user.password_updated',
            'module' => 'users',
            'description' => 'Account password updated.',
            'auditable' => $user,
        ], $request);

        return redirect()->route('profile.settings')->with('status', 'Your password has been updated.');
    }

    public function deactivate(DeactivateAccountRequest $request, AuditLogService $auditLogService): RedirectResponse
    {
        $user = $request->user();
        $user->status = 'inactive';
        $user->save();

        $auditLogService->log([
            'action' => 'user.deactivated',
            'event' => 'user.deactivated',
            'module' => 'users',
            'description' => 'Account deactivated by owner.',
            'auditable' => $user,
        ], $request);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Your account has been deactivated.');
    }

    public function security(Request $request): View
    {
        $user = $request->user();
        $now = CarbonImmutable::now();

        $windows = [
            '12h' => $now->subHours(12),
            '24h' => $now->subDay(),
            '7d' => $now->subDays(7),
        ];

        $stats = [];

        foreach ($windows as $key => $since) {
            $stats[$key] = [
                'logins' => AuditLog::where('user_id', $user->id)
                    ->where('action', AuditLog::ACTION_LOGIN)
                    ->where('created_at', '>=', $since)
                    ->count(),
                'logouts' => AuditLog::where('user_id', $user->id)
                    ->where('action', AuditLog::ACTION_LOGOUT)
                    ->where('created_at', '>=', $since)
                    ->count(),
                'failed' => AuditLog::where('user_id', $user->id)
                    ->where('action', AuditLog::ACTION_FAILED_LOGIN)
                    ->where('created_at', '>=', $since)
                    ->count(),
            ];
        }

        $recentAlerts = AuditLog::where('user_id', $user->id)
            ->whereIn('action', [AuditLog::ACTION_LOGIN, AuditLog::ACTION_FAILED_LOGIN, 'user.password_updated', 'user.email_updated'])
            ->latest('created_at')
            ->limit(3)
            ->get();

        return view('pages.apps.profile.security', [
            'user' => $user,
            'stats' => $stats,
            'recentAlerts' => $recentAlerts,
        ]);
    }

    public function activity(Request $request): View
    {
        $user = $request->user();
        $now = CarbonImmutable::now();

        $ranges = [
            'today' => [$now->startOfDay(), $now->endOfDay()],
            'week' => [$now->startOfWeek(), $now->endOfWeek()],
            'month' => [$now->startOfMonth(), $now->endOfMonth()],
            'year' => [$now->startOfYear(), $now->endOfYear()],
        ];

        $activity = [];

        foreach ($ranges as $key => [$start, $end]) {
            $activity[$key] = AuditLog::where('user_id', $user->id)
                ->whereBetween('created_at', [$start, $end])
                ->latest('created_at')
                ->limit(50)
                ->get();
        }

        return view('pages.apps.profile.activity', [
            'user' => $user,
            'activity' => $activity,
            'currentYear' => $now->year,
        ]);
    }

    public function billing(Request $request): View
    {
        $user = $request->user();
        $tenant = $user->tenant;

        $subscription = $tenant
            ? Subscription::where('tenant_id', $tenant->id)->with('plan')->latest('starts_at')->first()
            : null;

        $seatUsage = $tenant
            ? [
                'used' => $tenant->users()->count(),
                'limit' => $subscription?->plan?->max_users,
            ]
            : ['used' => 0, 'limit' => null];

        $subscriptions = $tenant
            ? Subscription::where('tenant_id', $tenant->id)->with('plan')->orderByDesc('starts_at')->get()
            : collect();

        return view('pages.apps.profile.billing', [
            'user' => $user,
            'tenant' => $tenant,
            'subscription' => $subscription,
            'subscriptions' => $subscriptions,
            'seatUsage' => $seatUsage,
            'address' => $user->default_address,
            'hasPaymentMethod' => false,
        ]);
    }

    public function statements(Request $request): View
    {
        $tenant = $request->user()->tenant;

        $subscriptions = $tenant
            ? Subscription::where('tenant_id', $tenant->id)->with('plan')->orderByDesc('starts_at')->get()
            : collect();

        return view('pages.apps.profile.statements', [
            'subscriptions' => $subscriptions->groupBy(fn (Subscription $subscription) => $subscription->starts_at?->year ?? 'Undated'),
        ]);
    }

    public function logs(Request $request): View
    {
        $user = $request->user();
        $hours = (int) $request->input('hours', 24);

        $loginSessions = AuditLog::where('user_id', $user->id)
            ->whereIn('action', [AuditLog::ACTION_LOGIN, AuditLog::ACTION_LOGOUT, AuditLog::ACTION_FAILED_LOGIN])
            ->where('created_at', '>=', now()->subHours($hours))
            ->latest('created_at')
            ->limit(20)
            ->get();

        $systemLogs = AuditLog::where('user_id', $user->id)
            ->latest('created_at')
            ->limit(50)
            ->get();

        return view('pages.apps.profile.logs', [
            'loginSessions' => $loginSessions,
            'systemLogs' => $systemLogs,
            'hours' => $hours,
        ]);
    }

    public function exportLogs(Request $request): StreamedResponse
    {
        $user = $request->user();
        $fileName = 'my-logs-' . now()->format('Ymd-His') . '.csv';

        $logs = AuditLog::where('user_id', $user->id)->latest('created_at')->limit(1000)->get();

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Action', 'Module', 'Description', 'IP Address', 'Device']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->created_at?->format('Y-m-d H:i:s'),
                    $log->action_label,
                    $log->module_label,
                    $log->description,
                    $log->ip_address,
                    $log->device,
                ]);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }
}
