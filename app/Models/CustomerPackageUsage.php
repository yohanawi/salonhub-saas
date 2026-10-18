<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPackageUsage extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'integer',
        'value' => 'decimal:2',
        'reversed_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(CustomerPackageItem::class, 'customer_package_item_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
