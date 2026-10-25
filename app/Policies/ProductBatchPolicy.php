<?php

namespace App\Policies;

use App\Models\ProductBatch;
use App\Models\User;
use App\Services\BranchContext;

class ProductBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('stock.view'));
    }

    /**
     * Writing a batch off (expired, damaged) removes stock, so it needs adjustment rights.
     */
    public function writeOff(User $user, ProductBatch $batch): bool
    {
        if ($batch->quantity_remaining <= 0) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ((int) $user->tenant_id !== (int) $batch->tenant_id || ! $user->can('stock.adjust')) {
            return false;
        }

        return app(BranchContext::class)->hasTenantWideBranchAccess($user)
            || $user->branches()->whereKey($batch->branch_id)->exists();
    }
}
