<?php

namespace App\Policies;

use App\Models\StockTransfer;
use App\Models\User;
use App\Services\BranchContext;

class StockTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('stock_transfer.manage'));
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function view(User $user, StockTransfer $transfer): bool
    {
        return $this->touchesBranch($user, $transfer, [$transfer->from_branch_id, $transfer->to_branch_id]);
    }

    /** Sending and cancelling belong to the branch the stock leaves. */
    public function send(User $user, StockTransfer $transfer): bool
    {
        return $transfer->status === StockTransfer::STATUS_DRAFT && $this->touchesBranch($user, $transfer, [$transfer->from_branch_id]);
    }

    public function cancel(User $user, StockTransfer $transfer): bool
    {
        return in_array($transfer->status, [StockTransfer::STATUS_DRAFT, StockTransfer::STATUS_IN_TRANSIT], true)
            && $this->touchesBranch($user, $transfer, [$transfer->from_branch_id]);
    }

    /** Receiving belongs to the branch the stock arrives at. */
    public function receive(User $user, StockTransfer $transfer): bool
    {
        return $transfer->status === StockTransfer::STATUS_IN_TRANSIT && $this->touchesBranch($user, $transfer, [$transfer->to_branch_id]);
    }

    private function touchesBranch(User $user, StockTransfer $transfer, array $branchIds): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ((int) $user->tenant_id !== (int) $transfer->tenant_id || ! $user->can('stock_transfer.manage')) {
            return false;
        }

        return app(BranchContext::class)->hasTenantWideBranchAccess($user)
            || $user->branches()->whereIn('branches.id', $branchIds)->exists();
    }
}
