<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipPlan extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'joining_fee' => 'decimal:2',
        'duration_value' => 'integer',
        'is_featured' => 'boolean',
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public function benefits(): HasMany
    {
        return $this->hasMany(MembershipBenefit::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(CustomerMembership::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status)->headline()->toString();
    }
}
