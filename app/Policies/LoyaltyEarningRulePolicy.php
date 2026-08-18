<?php

namespace App\Policies;

use App\Models\LoyaltyEarningRule;
use App\Models\User;

class LoyaltyEarningRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('loyalty.view'));
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('loyalty.manage'));
    }

    public function update(User $user, LoyaltyEarningRule $rule): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $rule->tenant_id && $user->can('loyalty.manage'));
    }
}
