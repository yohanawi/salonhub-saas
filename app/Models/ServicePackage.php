<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServicePackage extends Model
{
    use Auditable;
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'validity_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ServicePackageItem::class);
    }

    public function customerPackages(): HasMany
    {
        return $this->hasMany(CustomerPackage::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getSummaryAttribute(): string
    {
        return $this->items->map(fn (ServicePackageItem $item) => $item->quantity . ' x ' . ($item->service?->name ?? 'Service'))->implode(', ');
    }
}
