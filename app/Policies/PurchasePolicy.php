<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;
use App\Services\BranchContext;

class PurchasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('purchase.view'));
    }

    public function view(User $user, Purchase $purchase): bool
    {
        return $this->allowed($user, $purchase, 'purchase.view');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('purchase.manage'));
    }

    public function update(User $user, Purchase $purchase): bool
    {
        return $purchase->isEditable() && $this->allowed($user, $purchase, 'purchase.manage');
    }

    public function order(User $user, Purchase $purchase): bool
    {
        return $purchase->status === Purchase::STATUS_DRAFT && $this->allowed($user, $purchase, 'purchase.manage');
    }

    public function cancel(User $user, Purchase $purchase): bool
    {
        return $purchase->isCancellable() && $this->allowed($user, $purchase, 'purchase.manage');
    }

    public function receive(User $user, Purchase $purchase): bool
    {
        return $purchase->isReceivable() && $this->allowed($user, $purchase, 'purchase.receive');
    }

    private function allowed(User $user, Purchase $purchase, string $permission): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ((int) $user->tenant_id !== (int) $purchase->tenant_id || ! $user->can($permission)) {
            return false;
        }

        return app(BranchContext::class)->hasTenantWideBranchAccess($user)
            || $user->branches()->whereKey($purchase->branch_id)->exists();
    }
}
