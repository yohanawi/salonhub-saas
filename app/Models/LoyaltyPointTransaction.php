<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LoyaltyPointTransaction extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'points' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
        'earned_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public const TYPE_EARN = 'earn';
    public const TYPE_REDEEM = 'redeem';
    public const TYPE_EXPIRE = 'expire';
    public const TYPE_ADJUSTMENT_ADD = 'adjustment_add';
    public const TYPE_ADJUSTMENT_DEDUCT = 'adjustment_deduct';
    public const TYPE_REFUND_REVERSAL = 'refund_reversal';
    public const TYPE_BONUS = 'bonus';

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(CustomerLoyaltyAccount::class, 'loyalty_account_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return str($this->type)->replace('_', ' ')->headline()->toString();
    }
}
