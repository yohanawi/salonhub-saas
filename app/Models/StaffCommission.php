<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StaffCommission extends Model
{
    use Auditable;
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'base_amount' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'commission_base' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'earned_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_EARNED = 'earned';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';
    public const STATUS_REVERSED = 'reversed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_EARNED,
        self::STATUS_APPROVED,
        self::STATUS_PAID,
        self::STATUS_REVERSED,
        self::STATUS_CANCELLED,
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class, 'invoice_item_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(CommissionRule::class, 'commission_rule_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function reversedCommission(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_commission_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status)->replace('_', ' ')->headline()->toString();
    }

    /**
     * Approved, unpaid commissions that are not already claimed by an open commission payout or an open payroll run.
     * Commissions can be settled through either flow, so both must honour the other's claims to avoid paying twice.
     */
    public function scopeAvailableForSettlement(Builder $query): Builder
    {
        $table = $this->getTable();

        return $query
            ->where("{$table}.status", self::STATUS_APPROVED)
            ->whereNull("{$table}.paid_at")
            ->whereNotExists(function (QueryBuilder $sub) use ($table) {
                $sub->selectRaw('1')
                    ->from('commission_payout_items')
                    ->join('commission_payouts', 'commission_payouts.id', '=', 'commission_payout_items.commission_payout_id')
                    ->whereColumn('commission_payout_items.staff_commission_id', "{$table}.id")
                    ->whereIn('commission_payouts.status', [CommissionPayout::STATUS_PENDING, CommissionPayout::STATUS_APPROVED]);
            })
            ->whereNotExists(function (QueryBuilder $sub) use ($table) {
                $sub->selectRaw('1')
                    ->from('payroll_item_lines')
                    ->join('payroll_items', 'payroll_items.id', '=', 'payroll_item_lines.payroll_item_id')
                    ->join('payroll_runs', 'payroll_runs.id', '=', 'payroll_items.payroll_run_id')
                    ->where('payroll_item_lines.source_type', self::class)
                    ->whereColumn('payroll_item_lines.source_id', "{$table}.id")
                    ->whereIn('payroll_runs.status', PayrollRun::OPEN_STATUSES);
            });
    }
}
