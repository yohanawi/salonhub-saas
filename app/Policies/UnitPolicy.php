<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

class UnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('unit.view'));
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('unit.create'));
    }

    public function update(User $user, Unit $unit): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $unit->tenant_id && $user->can('unit.update'));
    }
}
