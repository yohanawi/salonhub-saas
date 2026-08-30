<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchSpecialHour extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'opens_at' => 'datetime:H:i',
        'closes_at' => 'datetime:H:i',
        'is_closed' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
