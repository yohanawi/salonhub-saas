<?php

namespace App\Policies;

use App\Models\CommissionRule;
use App\Models\User;

class CommissionRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('commission_rules.view'));
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('commission_rules.create'));
    }

    public function update(User $user, CommissionRule $rule): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $rule->tenant_id && $user->can('commission_rules.update'));
    }

    public function delete(User $user, CommissionRule $rule): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $rule->tenant_id && $user->can('commission_rules.delete'));
    }
}
