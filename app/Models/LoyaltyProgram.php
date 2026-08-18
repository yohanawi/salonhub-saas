<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyProgram extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'points_expiry_days' => 'integer',
        'minimum_redeem_points' => 'integer',
        'maximum_redeem_percentage' => 'decimal:2',
        'allow_partial_redemption' => 'boolean',
        'allow_points_on_discounted_sales' => 'boolean',
        'redemption_points' => 'integer',
        'redemption_value' => 'decimal:2',
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public function rules(): HasMany
    {
        return $this->hasMany(LoyaltyEarningRule::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(CustomerLoyaltyAccount::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status)->headline()->toString();
    }
}
