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
    // System methods are created by PaymentMethodService and settle against a gift card or a held deposit.
    public const TYPE_GIFT_CARD = 'gift_card';
    public const TYPE_DEPOSIT = 'deposit';

    public const SYSTEM_TYPES = [
        self::TYPE_GIFT_CARD,
        self::TYPE_DEPOSIT,
    ];

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

    /**
     * Methods a cashier can take a payment with at the POS. Deposits are only ever applied by checkout.
     */
    public function scopeTenderable(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('type', '!=', self::TYPE_DEPOSIT);
    }

    /**
     * Methods that move real money, for expenses, refunds and other payouts.
     */
    public function scopeManual(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNotIn('type', self::SYSTEM_TYPES);
    }

    public function isSystem(): bool
    {
        return in_array($this->type, self::SYSTEM_TYPES, true);
    }

    public function getTypeLabelAttribute(): string
    {
        return str($this->type)->replace('_', ' ')->headline()->toString();
    }
}
