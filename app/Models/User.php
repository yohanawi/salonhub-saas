<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'branch_id',
        'first_name',
        'last_name',
        'name',
        'email',
        'phone',
        'email_verified_at',
        'onboarded_at',
        'password',
        'status',
        'last_login_at',
        'last_login_ip',
        'profile_photo_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'onboarded_at' => 'datetime',
        'last_login_at' => 'datetime',
        'invited_at' => 'datetime',
        'invitation_accepted_at' => 'datetime',
        'two_factor_secret' => 'encrypted',
        'two_factor_recovery_codes' => 'encrypted:array',
        'two_factor_confirmed_at' => 'datetime',
    ];

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    public function hasPendingInvitation(): bool
    {
        return $this->invited_at !== null && $this->invitation_accepted_at === null;
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Why this user may not sign in (deactivated account or suspended salon), or null when they may.
     */
    public function loginBlockedReason(): ?string
    {
        // A null status only occurs on unsaved/in-memory models; the column defaults to "active".
        if (filled($this->status) && $this->status !== 'active') {
            return 'This account has been deactivated. Please contact support.';
        }

        if ($this->tenant_id && $this->tenant?->isSuspended() && ! $this->hasRole('Super Admin')) {
            return 'This salon account has been suspended. Please contact support.';
        }

        return null;
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class)
            ->withPivot('tenant_id')
            ->withTimestamps();
    }

    public function staffProfile(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    public function getProfilePhotoUrlAttribute()
    {
        if ($this->profile_photo_path) {
            return asset('storage/' . $this->profile_photo_path);
        }

        return $this->profile_photo_path;
    }

    public function addresses()
    {
        // Primary first, then in the order they were added.
        return $this->hasMany(Address::class)->orderByDesc('is_primary')->orderBy('id');
    }

    public function getDefaultAddressAttribute()
    {
        return $this->addresses?->first();
    }
}
