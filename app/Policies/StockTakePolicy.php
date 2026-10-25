<?php

namespace App\Policies;

use App\Models\StockTake;
use App\Models\User;
use App\Services\BranchContext;

class StockTakePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('stock_take.manage'));
    }

    public function view(User $user, StockTake $stockTake): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ((int) $user->tenant_id !== (int) $stockTake->tenant_id || ! $user->can('stock_take.manage')) {
            return false;
        }

        return app(BranchContext::class)->hasTenantWideBranchAccess($user)
            || $user->branches()->whereKey($stockTake->branch_id)->exists();
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, StockTake $stockTake): bool
    {
        return $stockTake->isOpen() && $this->view($user, $stockTake);
    }
}
