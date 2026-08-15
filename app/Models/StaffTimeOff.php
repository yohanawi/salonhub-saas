<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffTimeOff extends Model
{
    use BelongsToTenant;

    protected $table = 'staff_time_off';

    protected $guarded = [];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
    ];

    public const TYPES = [
        'annual_leave',
        'sick_leave',
        'personal_leave',
        'training',
        'unavailable',
        'other',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
