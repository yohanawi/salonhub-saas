<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitlistEntry extends Model
{
    use Auditable;
    use BelongsToTenant;

    public const STATUS_WAITING = 'waiting';
    public const STATUS_BOOKED = 'booked';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    public const STATUSES = [
        self::STATUS_WAITING,
        self::STATUS_BOOKED,
        self::STATUS_CANCELLED,
        self::STATUS_EXPIRED,
    ];

    protected $guarded = [];

    protected $casts = [
        'preferred_date' => 'date',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function timeWindowLabel(): string
    {
        if (! $this->preferred_from && ! $this->preferred_to) {
            return 'Any time';
        }

        $format = fn (?string $time) => $time ? \Carbon\Carbon::parse($time)->format('h:i A') : null;

        return ($format($this->preferred_from) ?? 'Opening') . ' – ' . ($format($this->preferred_to) ?? 'Closing');
    }
}
