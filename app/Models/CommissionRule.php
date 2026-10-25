<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionRule extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'commission_value' => 'decimal:2',
        'tiers' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /** Rate depends on how much the staff member has sold in the calendar month (see `tiers`). */
    public const TYPE_TIERED = 'tiered';

    public const TYPES = [
        CommissionSetting::TYPE_PERCENTAGE,
        CommissionSetting::TYPE_FIXED,
        self::TYPE_TIERED,
        CommissionSetting::TYPE_NONE,
    ];

    public const SCOPE_TENANT = 'tenant';
    public const SCOPE_BRANCH = 'branch';
    public const SCOPE_STAFF = 'staff';
    public const SCOPE_SERVICE = 'service';
    public const SCOPE_STAFF_SERVICE = 'staff_service';
    public const SCOPE_PRODUCT = 'product';
    public const SCOPE_STAFF_PRODUCT = 'staff_product';

    public const SCOPES = [
        self::SCOPE_TENANT,
        self::SCOPE_BRANCH,
        self::SCOPE_STAFF,
        self::SCOPE_SERVICE,
        self::SCOPE_STAFF_SERVICE,
        self::SCOPE_PRODUCT,
        self::SCOPE_STAFF_PRODUCT,
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function staffCommissions(): HasMany
    {
        return $this->hasMany(StaffCommission::class);
    }

    public function isTiered(): bool
    {
        return $this->commission_type === self::TYPE_TIERED;
    }

    /**
     * Tiers sorted from the lowest sales threshold up, as [['min_sales' => float, 'rate' => float], ...].
     */
    public function sortedTiers(): array
    {
        return collect($this->tiers ?? [])
            ->map(fn (array $tier) => ['min_sales' => (float) $tier['min_sales'], 'rate' => (float) $tier['rate']])
            ->sortBy('min_sales')
            ->values()
            ->all();
    }

    public function scopeActiveForDate(Builder $query, mixed $date): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $query) use ($date) {
                $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $date);
            })
            ->where(function (Builder $query) use ($date) {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            });
    }
}
