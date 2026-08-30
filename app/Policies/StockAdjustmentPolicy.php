<?php

namespace App\Policies;

use App\Models\StockAdjustment;
use App\Models\User;
use App\Services\BranchContext;

class StockAdjustmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('stock.adjust'));
    }

    public function view(User $user, StockAdjustment $adjustment): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ((int) $user->tenant_id !== (int) $adjustment->tenant_id || ! $user->can('stock.adjust')) {
            return false;
        }

        return app(BranchContext::class)->hasTenantWideBranchAccess($user)
            || $user->branches()->whereKey($adjustment->branch_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('stock.adjust'));
    }
}
