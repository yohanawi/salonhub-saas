<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    use BelongsToTenant;
    use BelongsToBranch;

    public const STATUS_APPROVED = 'approved';

    public const REASON_DAMAGED = 'damaged';
    public const REASON_EXPIRED = 'expired';
    public const REASON_LOST = 'lost';
    public const REASON_PHYSICAL_COUNT = 'physical_count_correction';
    public const REASON_SYSTEM_ERROR = 'system_error_correction';
    public const REASON_INTERNAL_USE = 'internal_use';
    public const REASON_PROMOTIONAL_GIVEAWAY = 'promotional_giveaway';
    public const REASON_OTHER = 'other';

    public const REASONS = [
        self::REASON_DAMAGED,
        self::REASON_EXPIRED,
        self::REASON_LOST,
        self::REASON_PHYSICAL_COUNT,
        self::REASON_SYSTEM_ERROR,
        self::REASON_INTERNAL_USE,
        self::REASON_PROMOTIONAL_GIVEAWAY,
        self::REASON_OTHER,
    ];

    protected $guarded = [];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getReasonLabelAttribute(): string
    {
        return str($this->reason)->replace('_', ' ')->headline()->toString();
    }
}
