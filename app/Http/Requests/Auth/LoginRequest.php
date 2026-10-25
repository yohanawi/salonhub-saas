<?php

namespace App\Http\Requests\Auth;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Settings\SettingService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate()
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            app(AuditLogService::class)->recordAuthentication(
                AuditLog::ACTION_FAILED_LOGIN,
                User::query()->where('email', $this->input('email'))->first(),
                $this,
                ['email' => $this->input('email')]
            );

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        if ($blockedReason = Auth::user()->loginBlockedReason()) {
            $user = Auth::user();
            Auth::logout();

            app(AuditLogService::class)->recordAuthentication(
                AuditLog::ACTION_FAILED_LOGIN,
                $user,
                $this,
                ['email' => $this->input('email'), 'reason' => $user->status !== 'active' ? 'account_inactive' : 'tenant_suspended']
            );

            throw ValidationException::withMessages([
                'email' => $blockedReason,
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited()
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $this->maxAttempts())) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Failed sign-ins allowed before a lockout: the account's salon sets it in Settings > Security.
     * Unknown emails get the default, so the limit does not reveal which addresses have accounts.
     */
    private function maxAttempts(): int
    {
        $tenantId = User::query()->where('email', $this->input('email'))->value('tenant_id');
        $limit = $tenantId ? (int) app(SettingService::class)->get('security.login_attempt_limit', $tenantId) : 0;

        return $limit > 0 ? $limit : 5;
    }

    /**
     * Get the rate limiting throttle key for the request.
     *
     * @return string
     */
    public function throttleKey()
    {
        return Str::transliterate(Str::lower($this->input('email')).'|'.$this->ip());
    }
}
