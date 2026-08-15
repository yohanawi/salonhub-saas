<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ON_LEAVE = 'on_leave';
    public const STATUS_TERMINATED = 'terminated';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_ON_LEAVE,
        self::STATUS_TERMINATED,
    ];

    public const GENDERS = [
        'female',
        'male',
        'other',
        'prefer_not_to_say',
    ];

    protected $guarded = [];

    protected $casts = [
        'commission_value' => 'decimal:2',
        'hire_date' => 'date',
        'date_of_birth' => 'date',
        'is_bookable' => 'boolean',
        'show_online' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'staff_branches')
            ->withPivot('tenant_id', 'is_primary', 'status')
            ->withTimestamps();
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(StaffSchedule::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(StaffLeave::class);
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(StaffBreak::class);
    }

    public function timeOff(): HasMany
    {
        return $this->hasMany(StaffTimeOff::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'staff_services')
            ->withPivot('tenant_id', 'custom_duration_minutes', 'custom_price', 'status')
            ->withTimestamps();
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(StaffCommission::class);
    }

    public function commissionSettings(): HasMany
    {
        return $this->hasMany(StaffCommissionSetting::class);
    }

    public function scopeAssignedToBranch(Builder $query, Branch|int $branch): Builder
    {
        $branchId = $branch instanceof Branch ? $branch->getKey() : $branch;

        return $query->whereHas('branches', fn (Builder $query) => $query->where('branches.id', $branchId));
    }

    public function scopeBookable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)->where('is_bookable', true);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status)->replace('_', ' ')->headline()->toString();
    }

    public function primaryBranch(): ?Branch
    {
        return $this->branches->first(fn (Branch $branch) => (bool) $branch->pivot?->is_primary);
    }
}
