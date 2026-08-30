<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollItem extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'commission_enabled_snapshot' => 'boolean',
        'basic_salary_snapshot' => 'decimal:2',
        'hourly_rate_snapshot' => 'decimal:2',
        'daily_rate_snapshot' => 'decimal:2',
        'basic_pay' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'allowance_amount' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'other_earnings' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'advance_deduction' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public const STATUS_CALCULATED = 'calculated';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(StaffSalaryStructure::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PayrollItemLine::class);
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
