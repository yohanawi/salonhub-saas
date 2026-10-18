<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiftCardTransaction extends Model
{
    use BelongsToTenant;

    public const TYPE_ISSUE = 'issue';
    public const TYPE_REDEEM = 'redeem';
    public const TYPE_REVERSE = 'reverse';
    public const TYPE_VOID = 'void';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
