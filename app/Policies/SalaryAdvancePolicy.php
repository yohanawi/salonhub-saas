<?php

namespace App\Policies;

use App\Models\SalaryAdvance;
use App\Models\User;

class SalaryAdvancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('advance.manage'));
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function manage(User $user, SalaryAdvance $advance): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $advance->tenant_id && $user->can('advance.manage'));
    }
}
