<?php

namespace App\Policies;

use App\Models\PromotionUsage;
use App\Models\User;

class PromotionUsagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('promotion_usage.view'));
    }

    public function view(User $user, PromotionUsage $usage): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $usage->tenant_id && $user->can('promotion_usage.view'));
    }
}
