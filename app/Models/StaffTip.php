<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffTip extends Model
{
    use BelongsToTenant;

    // Pending until the invoice is fully paid, then earned and picked up by the next payroll run.
    public const STATUS_PENDING = 'pending';
    public const STATUS_EARNED = 'earned';
    public const STATUS_PAID = 'paid';
    public const STATUS_VOID = 'void';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'earned_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class)->withTrashed();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
