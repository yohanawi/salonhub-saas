<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    use BelongsToTenant;

    public const TYPE_CASH = 'cash';
    public const TYPE_CARD = 'card';
    public const TYPE_BANK_TRANSFER = 'bank_transfer';
    public const TYPE_QR = 'qr';
    public const TYPE_ONLINE = 'online';
    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_CASH,
        self::TYPE_CARD,
        self::TYPE_BANK_TRANSFER,
        self::TYPE_QR,
        self::TYPE_ONLINE,
        self::TYPE_OTHER,
    ];

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_reference' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function expensePayments(): HasMany
    {
        return $this->hasMany(ExpensePayment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getTypeLabelAttribute(): string
    {
        return str($this->type)->replace('_', ' ')->headline()->toString();
    }
}
