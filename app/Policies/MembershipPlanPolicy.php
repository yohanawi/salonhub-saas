<?php

namespace App\Policies;

use App\Models\MembershipPlan;
use App\Models\User;

class MembershipPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('membership_plans.view'));
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('membership_plans.create'));
    }

    public function update(User $user, MembershipPlan $plan): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $plan->tenant_id && $user->can('membership_plans.edit'));
    }
}
