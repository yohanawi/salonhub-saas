<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerPackage extends Model
{
    use Auditable;
    use BelongsToTenant;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = [];

    protected $casts = [
        'price_paid' => 'decimal:2',
        'purchased_at' => 'datetime',
        'expires_at' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class, 'service_package_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerPackageItem::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->copy()->endOfDay()->isPast();
    }

    /**
     * Packages whose credits can still be used today.
     */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhereDate('expires_at', '>=', today()));
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->status === self::STATUS_ACTIVE && $this->isExpired()) {
            return 'Expired';
        }

        return str($this->status)->headline()->toString();
    }
}
