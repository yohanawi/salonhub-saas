<?php

namespace App\Models\Concerns;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToBranch
{
    public function scopeForBranch(Builder $query, Branch|int $branch): Builder
    {
        $branchId = $branch instanceof Branch ? $branch->getKey() : $branch;

        return $query->where($query->getModel()->getTable() . '.branch_id', $branchId);
    }
}
