<?php

namespace App\Policies;

use App\Models\CustomerMembership;
use App\Models\User;

class CustomerMembershipPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('memberships.view'));
    }

    public function view(User $user, CustomerMembership $membership): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $membership->tenant_id && $user->can('memberships.view'));
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('memberships.create'));
    }

    public function cancel(User $user, CustomerMembership $membership): bool
    {
        return $this->view($user, $membership) && ($user->hasRole('Super Admin') || $user->can('memberships.cancel'));
    }
}
