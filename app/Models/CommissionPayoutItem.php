<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionPayoutItem extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payout(): BelongsTo
    {
        return $this->belongsTo(CommissionPayout::class, 'commission_payout_id');
    }

    public function commission(): BelongsTo
    {
        return $this->belongsTo(StaffCommission::class, 'staff_commission_id');
    }
}
