<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerLoyaltyAccount extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'available_points' => 'integer',
        'pending_points' => 'integer',
        'lifetime_earned_points' => 'integer',
        'lifetime_redeemed_points' => 'integer',
        'expired_points' => 'integer',
        'joined_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class, 'loyalty_program_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyPointTransaction::class, 'loyalty_account_id');
    }
}
