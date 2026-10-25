<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSchedule extends Model
{
    use BelongsToTenant;

    public const FREQUENCIES = ['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'];

    protected $guarded = [];

    protected $casts = [
        'recipients' => 'array',
        'is_active' => 'boolean',
        'day_of_week' => 'integer',
        'day_of_month' => 'integer',
        'last_sent_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Next send time after $after, in the salon's timezone (stored as UTC-equivalent wall time like the rest of the app).
     */
    public function computeNextRun(?CarbonImmutable $after = null): CarbonImmutable
    {
        $timezone = $this->tenant?->timezone ?: config('app.timezone');
        $after ??= CarbonImmutable::now($timezone);
        [$hour, $minute] = array_map('intval', explode(':', $this->send_time ?: '08:00'));

        $candidate = $after->setTime($hour, $minute);

        $candidate = match ($this->frequency) {
            'weekly' => $candidate->addDays((($this->day_of_week ?: 1) - $candidate->dayOfWeekIso + 7) % 7),
            'monthly' => $this->dayInMonth($candidate),
            default => $candidate,
        };

        if ($candidate->lessThanOrEqualTo($after)) {
            $candidate = match ($this->frequency) {
                'weekly' => $candidate->addWeek(),
                'monthly' => $this->dayInMonth($candidate->startOfMonth()->addMonthNoOverflow())->setTime($hour, $minute),
                default => $candidate->addDay(),
            };
        }

        return $candidate;
    }

    /** The scheduled day in $month, clamped to short months (day 31 → 30 Apr, 28/29 Feb). */
    private function dayInMonth(CarbonImmutable $month): CarbonImmutable
    {
        return $month->setDate($month->year, $month->month, min($this->day_of_month ?: 1, $month->daysInMonth));
    }

    /**
     * The period a run covers: yesterday, the previous Monday–Sunday week, or the previous calendar month.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function periodFor(CarbonImmutable $runAt): array
    {
        return match ($this->frequency) {
            'weekly' => [$runAt->subWeek()->startOfWeek(), $runAt->subWeek()->endOfWeek()],
            'monthly' => [$runAt->subMonthNoOverflow()->startOfMonth(), $runAt->subMonthNoOverflow()->endOfMonth()],
            default => [$runAt->subDay()->startOfDay(), $runAt->subDay()->endOfDay()],
        };
    }

    public function getScheduleLabelAttribute(): string
    {
        $days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

        return match ($this->frequency) {
            'weekly' => 'Every ' . ($days[$this->day_of_week ?: 1] ?? 'Monday') . " at {$this->send_time}",
            'monthly' => 'Monthly on day ' . ($this->day_of_month ?: 1) . " at {$this->send_time}",
            default => "Daily at {$this->send_time}",
        };
    }
}
