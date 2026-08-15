<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffCommissionSetting extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'commission_value' => 'decimal:2',
    ];

    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED = 'fixed';

    public const TYPES = [
        self::TYPE_PERCENTAGE,
        self::TYPE_FIXED,
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
