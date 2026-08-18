<?php

namespace App\Policies;

use App\Models\Promotion;
use App\Models\User;

class PromotionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('promotions.view'));
    }

    public function view(User $user, Promotion $promotion): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $promotion->tenant_id && $user->can('promotions.view'));
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('promotions.create'));
    }

    public function update(User $user, Promotion $promotion): bool
    {
        return $this->view($user, $promotion) && ($user->hasRole('Super Admin') || $user->can('promotions.update'));
    }

    public function delete(User $user, Promotion $promotion): bool
    {
        return $this->view($user, $promotion) && ($user->hasRole('Super Admin') || $user->can('promotions.delete'));
    }
}
