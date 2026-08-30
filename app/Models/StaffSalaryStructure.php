<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffSalaryStructure extends Model
{
    use Auditable;
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'daily_rate' => 'decimal:2',
        'overtime_enabled' => 'boolean',
        'overtime_rate' => 'decimal:2',
        'commission_enabled' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public const TYPE_MONTHLY = 'monthly';
    public const TYPE_DAILY = 'daily';
    public const TYPE_HOURLY = 'hourly';
    public const TYPE_COMMISSION_ONLY = 'commission_only';
    public const TYPE_SALARY_COMMISSION = 'salary_commission';
    public const TYPES = [
        self::TYPE_MONTHLY,
        self::TYPE_DAILY,
        self::TYPE_HOURLY,
        self::TYPE_COMMISSION_ONLY,
        self::TYPE_SALARY_COMMISSION,
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function scopeEffectiveFor(Builder $query, mixed $date): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereDate('effective_from', '<=', $date)
            ->where(function (Builder $query) use ($date) {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            });
    }
}
