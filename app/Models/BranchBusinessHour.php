<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchBusinessHour extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'is_closed' => 'boolean',
        'opens_at' => 'datetime:H:i',
        'closes_at' => 'datetime:H:i',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
