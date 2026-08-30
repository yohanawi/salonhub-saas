<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }
}
