<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use BelongsToTenant;
    use BelongsToBranch;

    public const TYPE_OPENING_STOCK = 'opening_stock';
    public const TYPE_PURCHASE_RECEIPT = 'purchase_receipt';
    public const TYPE_POS_SALE = 'pos_sale';
    public const TYPE_SALE_RETURN = 'sale_return';
    public const TYPE_SERVICE_CONSUMPTION = 'service_consumption';
    public const TYPE_ADJUSTMENT_IN = 'adjustment_in';
    public const TYPE_ADJUSTMENT_OUT = 'adjustment_out';
    public const TYPE_TRANSFER_IN = 'transfer_in';
    public const TYPE_TRANSFER_OUT = 'transfer_out';
    public const TYPE_DAMAGED = 'damaged';
    public const TYPE_EXPIRED = 'expired';
    public const TYPE_LOST = 'lost';
    public const TYPE_MANUAL_CORRECTION = 'manual_correction';

    public const TYPES = [
        self::TYPE_OPENING_STOCK,
        self::TYPE_PURCHASE_RECEIPT,
        self::TYPE_POS_SALE,
        self::TYPE_SALE_RETURN,
        self::TYPE_SERVICE_CONSUMPTION,
        self::TYPE_ADJUSTMENT_IN,
        self::TYPE_ADJUSTMENT_OUT,
        self::TYPE_TRANSFER_IN,
        self::TYPE_TRANSFER_OUT,
        self::TYPE_DAMAGED,
        self::TYPE_EXPIRED,
        self::TYPE_LOST,
        self::TYPE_MANUAL_CORRECTION,
    ];

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function getTypeLabelAttribute(): string
    {
        return str($this->type)->replace('_', ' ')->headline()->toString();
    }
}
