<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTakeItem extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'system_quantity' => 'integer',
        'counted_quantity' => 'integer',
        'variance' => 'integer',
    ];

    public function stockTake(): BelongsTo
    {
        return $this->belongsTo(StockTake::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
