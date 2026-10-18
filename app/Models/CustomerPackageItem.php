<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerPackageItem extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'total_quantity' => 'integer',
        'used_quantity' => 'integer',
    ];

    public function customerPackage(): BelongsTo
    {
        return $this->belongsTo(CustomerPackage::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CustomerPackageUsage::class);
    }

    public function remainingQuantity(): int
    {
        return max(0, $this->total_quantity - $this->used_quantity);
    }
}
