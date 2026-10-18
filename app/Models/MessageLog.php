<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MessageLog extends Model
{
    use BelongsToTenant;

    public const CHANNEL_SMS = 'sms';
    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    public const EVENT_RECEIPT = 'receipt';
    public const EVENT_BOOKING_CONFIRMATION = 'booking_confirmation';
    public const EVENT_REMINDER = 'reminder';
    public const EVENT_TEST = 'test';

    protected $guarded = [];

    protected $casts = [
        'sent_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getEventLabelAttribute(): string
    {
        return str($this->event)->replace('_', ' ')->headline()->toString();
    }
}
