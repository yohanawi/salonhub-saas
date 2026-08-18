<?php

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;
use App\Services\BranchContext;

class StockMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('stock_movement.view'));
    }

    public function view(User $user, StockMovement $movement): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ((int) $user->tenant_id !== (int) $movement->tenant_id || ! $user->can('stock_movement.view')) {
            return false;
        }

        return app(BranchContext::class)->hasTenantWideBranchAccess($user)
            || $user->branches()->whereKey($movement->branch_id)->exists();
    }
}
