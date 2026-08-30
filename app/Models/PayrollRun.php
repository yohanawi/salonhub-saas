<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'employees_count' => 'integer',
        'basic_salary_total' => 'decimal:2',
        'commission_total' => 'decimal:2',
        'earnings_total' => 'decimal:2',
        'deductions_total' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'calculated_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_CALCULATED = 'calculated';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_CALCULATED,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_APPROVED,
        self::STATUS_PAID,
        self::STATUS_CLOSED,
        self::STATUS_CANCELLED,
    ];

    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_PARTIAL = 'partially_paid';
    public const PAYMENT_PAID = 'paid';

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PayrollPayment::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status)->replace('_', ' ')->headline()->toString();
    }
}
