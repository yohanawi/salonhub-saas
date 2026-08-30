<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PayrollItemLine extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'snapshot' => 'array',
    ];

    public const TYPE_EARNING = 'earning';
    public const TYPE_DEDUCTION = 'deduction';

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
