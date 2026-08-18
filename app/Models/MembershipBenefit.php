<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipBenefit extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'usage_limit' => 'integer',
        'loyalty_multiplier' => 'decimal:2',
        'priority' => 'integer',
    ];

    public const TYPE_SERVICE_DISCOUNT = 'service_discount';
    public const TYPE_PRODUCT_DISCOUNT = 'product_discount';
    public const TYPE_FREE_SERVICE = 'free_service';
    public const TYPE_BONUS_POINTS_MULTIPLIER = 'bonus_points_multiplier';

    public const DISCOUNT_PERCENTAGE = 'percentage';
    public const DISCOUNT_FIXED = 'fixed';

    public const STATUS_ACTIVE = 'active';

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'membership_plan_id');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(MembershipBenefitUsage::class);
    }
}
