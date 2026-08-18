<?php

namespace App\Policies;

use App\Models\StaffSalaryStructure;
use App\Models\User;

class StaffSalaryStructurePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('salary.view'));
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('salary.manage'));
    }

    public function update(User $user, StaffSalaryStructure $structure): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $structure->tenant_id && $user->can('salary.manage'));
    }
}
