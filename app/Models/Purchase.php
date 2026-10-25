<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use BelongsToTenant;
    use BelongsToBranch;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ORDERED = 'ordered';
    public const STATUS_PARTIALLY_RECEIVED = 'partially_received';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ORDERED,
        self::STATUS_PARTIALLY_RECEIVED,
        self::STATUS_RECEIVED,
        self::STATUS_CANCELLED,
    ];

    /** Statuses that still expect goods to arrive. */
    public const RECEIVABLE_STATUSES = [
        self::STATUS_ORDERED,
        self::STATUS_PARTIALLY_RECEIVED,
    ];

    protected $guarded = [];

    protected $casts = [
        'purchase_date' => 'date',
        'expected_date' => 'date',
        'ordered_at' => 'datetime',
        'received_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(PurchaseReceipt::class);
    }

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isReceivable(): bool
    {
        return in_array($this->status, self::RECEIVABLE_STATUSES, true);
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_ORDERED], true);
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status)->replace('_', ' ')->headline()->toString();
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ORDERED => 'primary',
            self::STATUS_PARTIALLY_RECEIVED => 'warning',
            self::STATUS_RECEIVED => 'success',
            self::STATUS_CANCELLED => 'danger',
            default => 'secondary',
        };
    }
}
