<?php

namespace App\Policies;

use App\Models\LoyaltyPointTransaction;
use App\Models\User;

class LoyaltyPointTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('loyalty.view'));
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('loyalty.adjust_points'));
    }

    public function view(User $user, LoyaltyPointTransaction $transaction): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $transaction->tenant_id && $user->can('loyalty.view'));
    }
}
