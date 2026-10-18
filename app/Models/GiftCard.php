<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GiftCard extends Model
{
    use Auditable;
    use BelongsToTenant;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_REDEEMED = 'redeemed';
    public const STATUS_VOID = 'void';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_REDEEMED,
        self::STATUS_VOID,
    ];

    protected $guarded = [];

    protected $casts = [
        'initial_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'expires_at' => 'date',
        'voided_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(GiftCardTransaction::class)->latest('id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->copy()->endOfDay()->isPast();
    }

    public function isUsable(): bool
    {
        return $this->status === self::STATUS_ACTIVE && ! $this->isExpired() && (float) $this->balance > 0;
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->status === self::STATUS_ACTIVE && $this->isExpired()) {
            return 'Expired';
        }

        return str($this->status)->headline()->toString();
    }

    public static function normalizeCode(?string $code): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $code));
    }
}
