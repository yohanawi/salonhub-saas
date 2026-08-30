<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use BelongsToTenant;

    public const TYPE_PIECE = 'piece';
    public const TYPE_VOLUME = 'volume';
    public const TYPE_WEIGHT = 'weight';
    public const TYPE_LENGTH = 'length';
    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_PIECE,
        self::TYPE_VOLUME,
        self::TYPE_WEIGHT,
        self::TYPE_LENGTH,
        self::TYPE_OTHER,
    ];

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return str($this->type)->replace('_', ' ')->headline()->toString();
    }
}
