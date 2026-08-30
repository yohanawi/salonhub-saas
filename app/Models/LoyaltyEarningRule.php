<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyEarningRule extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'spend_amount' => 'decimal:2',
        'points_awarded' => 'integer',
        'minimum_purchase_amount' => 'decimal:2',
        'maximum_points_per_transaction' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'priority' => 'integer',
    ];

    public const TYPE_SPEND = 'spend';
    public const TYPE_BONUS = 'bonus';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class, 'loyalty_program_id');
    }

    public function scopeActiveFor(Builder $query, mixed $date): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function (Builder $query) use ($date) {
                $query->whereNull('start_date')->orWhereDate('start_date', '<=', $date);
            })
            ->where(function (Builder $query) use ($date) {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', $date);
            });
    }
}
