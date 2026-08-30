<?php

namespace App\Policies;

use App\Models\LoyaltyProgram;
use App\Models\User;

class LoyaltyProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('loyalty.view'));
    }

    public function view(User $user, LoyaltyProgram $program): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $program->tenant_id && $user->can('loyalty.view'));
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('loyalty.manage'));
    }

    public function update(User $user, LoyaltyProgram $program): bool
    {
        return $this->view($user, $program) && ($user->hasRole('Super Admin') || $user->can('loyalty.manage'));
    }
}
