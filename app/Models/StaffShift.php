<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A rostered shift for one date. It overrides the staff member's weekly schedule for that day;
 * an "off" shift marks a rostered day off.
 */
class StaffShift extends Model
{
    use BelongsToTenant;
    use BelongsToBranch;

    protected $guarded = [];

    protected $casts = [
        'shift_date' => 'date',
        'is_off' => 'boolean',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function getLabelAttribute(): string
    {
        return $this->is_off ? 'Off' : substr((string) $this->start_time, 0, 5) . '–' . substr((string) $this->end_time, 0, 5);
    }
}
