<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseItem extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_received' => 'integer',
        'unit_cost' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function receiptItems(): HasMany
    {
        return $this->hasMany(PurchaseReceiptItem::class);
    }

    public function getQuantityPendingAttribute(): int
    {
        return max(0, (int) $this->quantity - (int) $this->quantity_received);
    }
}
