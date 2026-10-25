<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryAdvance extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'recovered_amount' => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'request_date' => 'date',
        'approved_date' => 'date',
        'paid_date' => 'date',
    ];

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';
    public const STATUS_RECOVERED = 'recovered';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_REQUESTED,
        self::STATUS_APPROVED,
        self::STATUS_PAID,
        self::STATUS_RECOVERED,
        self::STATUS_REJECTED,
    ];

    public const REPAYMENT_ONE_TIME = 'one_time';
    public const REPAYMENT_INSTALLMENTS = 'installments';

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status)->headline()->toString();
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_REQUESTED => 'warning',
            self::STATUS_APPROVED => 'primary',
            self::STATUS_PAID => 'info',
            self::STATUS_RECOVERED => 'success',
            self::STATUS_REJECTED => 'danger',
            default => 'secondary',
        };
    }
}
