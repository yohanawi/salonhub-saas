<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_TEMPORARILY_CLOSED = 'temporarily_closed';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_TEMPORARILY_CLOSED,
    ];

    public const DAY_LABELS = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ];

    protected $guarded = [];

    protected $casts = [
        'opening_hours' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'tax_enabled' => 'boolean',
        'tax_rate' => 'decimal:4',
        'is_main' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('tenant_id')
            ->withTimestamps();
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->users();
    }

    public function legacyUsers(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'staff_branches')->withPivot('tenant_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'branch_services')
            ->withPivot('tenant_id', 'price', 'is_active');
    }

    public function businessHours(): HasMany
    {
        return $this->hasMany(BranchBusinessHour::class);
    }

    public function specialHours(): HasMany
    {
        return $this->hasMany(BranchSpecialHour::class);
    }

    public function getAddressSummaryAttribute(): string
    {
        return collect([
            $this->address_line_1 ?: $this->address,
            $this->address_line_2,
            $this->city,
            $this->district,
            $this->postal_code,
        ])->filter()->implode(', ');
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status ?? self::STATUS_ACTIVE)->replace('_', ' ')->headline()->toString();
    }
}
