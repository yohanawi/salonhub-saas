<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerMembership extends Model
{
    use Auditable;
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'price_paid' => 'decimal:2',
        'joining_fee_paid' => 'decimal:2',
        'auto_renew' => 'boolean',
        'cancelled_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_SUSPENDED = 'suspended';

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'membership_plan_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(MembershipBenefitUsage::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status)->headline()->toString();
    }
}
