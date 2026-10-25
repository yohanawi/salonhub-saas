<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A level customers reach by lifetime points earned (e.g. Silver 0, Gold 1,000, Platinum 5,000).
 */
class LoyaltyTier extends Model
{
    use BelongsToTenant;

    public const COLORS = ['secondary', 'warning', 'primary', 'info', 'success', 'danger', 'dark'];

    protected $guarded = [];

    protected $casts = [
        'min_points' => 'integer',
        'points_multiplier' => 'decimal:2',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class, 'loyalty_program_id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(CustomerLoyaltyAccount::class, 'loyalty_tier_id');
    }
}
