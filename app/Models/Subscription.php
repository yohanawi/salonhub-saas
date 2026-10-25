<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use Auditable;
    use BelongsToTenant;

    public const STATUS_TRIALING = 'trialing';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAST_DUE = 'past_due';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_TRIALING,
        self::STATUS_ACTIVE,
        self::STATUS_PAST_DUE,
        self::STATUS_CANCELLED,
    ];

    // Only these statuses let a salon use the system; past_due and cancelled are locked out.
    public const ACCESS_STATUSES = [
        self::STATUS_TRIALING,
        self::STATUS_ACTIVE,
    ];

    protected $guarded = [];

    protected $casts = [
        'entitlements' => 'array',
        'price' => 'decimal:2',
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Whether the salon may use the system right now. A trial without an end date never expires on its own.
     */
    public function grantsAccess(): bool
    {
        if (! in_array($this->status, self::ACCESS_STATUSES, true)) {
            return false;
        }

        if ($this->ends_at && ! $this->ends_at->isFuture()) {
            return false;
        }

        return $this->status !== self::STATUS_TRIALING
            || $this->trial_ends_at === null
            || $this->trial_ends_at->isFuture();
    }

    public function accessBlockedReason(): ?string
    {
        if ($this->grantsAccess()) {
            return null;
        }

        return match ($this->status) {
            self::STATUS_PAST_DUE => 'Your subscription payment is overdue. Please contact support to restore access.',
            self::STATUS_CANCELLED => 'Your subscription has been cancelled. Please contact support to reactivate it.',
            self::STATUS_TRIALING => 'Your free trial has ended. Please contact support to choose a plan.',
            default => 'Your subscription has ended. Please contact support to renew it.',
        };
    }

    /**
     * Query version of grantsAccess(), for scheduled jobs that loop over salons.
     */
    public function scopeGrantingAccess(Builder $query): Builder
    {
        $table = $query->getModel()->getTable();
        $now = now();

        return $query
            ->whereIn("{$table}.status", self::ACCESS_STATUSES)
            ->where(fn (Builder $query) => $query->whereNull("{$table}.ends_at")->orWhere("{$table}.ends_at", '>', $now))
            ->where(fn (Builder $query) => $query
                ->where("{$table}.status", '!=', self::STATUS_TRIALING)
                ->orWhereNull("{$table}.trial_ends_at")
                ->orWhere("{$table}.trial_ends_at", '>', $now));
    }
}
