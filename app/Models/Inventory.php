<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use Auditable;
    use BelongsToTenant;
    use BelongsToBranch;

    public const STATUS_IN_STOCK = 'in_stock';
    public const STATUS_LOW_STOCK = 'low_stock';
    public const STATUS_OUT_OF_STOCK = 'out_of_stock';

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_on_hand' => 'integer',
        'quantity_reserved' => 'integer',
        'average_cost' => 'decimal:2',
        'last_received_at' => 'datetime',
        'last_sold_at' => 'datetime',
        'last_adjusted_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getAvailableQuantityAttribute(): int
    {
        return max(0, (int) $this->quantity_on_hand - (int) $this->quantity_reserved);
    }

    public function getStockStatusAttribute(): string
    {
        if ((int) $this->quantity_on_hand <= 0) {
            return self::STATUS_OUT_OF_STOCK;
        }

        if ((int) $this->quantity_on_hand <= (int) ($this->product?->reorder_level ?? 0)) {
            return self::STATUS_LOW_STOCK;
        }

        return self::STATUS_IN_STOCK;
    }

    public function getStockStatusLabelAttribute(): string
    {
        return str($this->stock_status)->replace('_', ' ')->headline()->toString();
    }
}
