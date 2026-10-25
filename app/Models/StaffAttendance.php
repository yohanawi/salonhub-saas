<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendance extends Model
{
    use BelongsToTenant;
    use BelongsToBranch;

    public const STATUS_PRESENT = 'present';
    public const STATUS_LATE = 'late';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_LEAVE = 'leave';

    public const STATUSES = [self::STATUS_PRESENT, self::STATUS_LATE, self::STATUS_ABSENT, self::STATUS_LEAVE];

    protected $guarded = [];

    protected $casts = [
        'work_date' => 'date',
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
        'break_minutes' => 'integer',
        'worked_minutes' => 'integer',
        'late_minutes' => 'integer',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function isOpen(): bool
    {
        return $this->clock_in_at !== null && $this->clock_out_at === null;
    }

    public function getWorkedLabelAttribute(): string
    {
        $minutes = $this->worked_minutes ?? ($this->isOpen() ? (int) $this->clock_in_at->diffInMinutes(now()) - $this->break_minutes : 0);
        $minutes = max(0, (int) $minutes);

        return intdiv($minutes, 60) . 'h ' . str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT) . 'm';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_LATE => 'warning',
            self::STATUS_ABSENT => 'danger',
            self::STATUS_LEAVE => 'info',
            default => 'success',
        };
    }
}
