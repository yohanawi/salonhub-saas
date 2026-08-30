<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'pay_date' => 'date',
        'approved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_OPEN = 'open';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_CALCULATED = 'calculated';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_OPEN,
        self::STATUS_PROCESSING,
        self::STATUS_CALCULATED,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_APPROVED,
        self::STATUS_PAID,
        self::STATUS_CLOSED,
        self::STATUS_CANCELLED,
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(PayrollRun::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status)->replace('_', ' ')->headline()->toString();
    }
}
