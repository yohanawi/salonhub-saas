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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
