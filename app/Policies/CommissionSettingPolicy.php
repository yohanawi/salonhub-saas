<?php

namespace App\Policies;

use App\Models\CommissionSetting;
use App\Models\User;

class CommissionSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('commission_settings.manage'));
    }

    public function update(User $user, CommissionSetting $setting): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $setting->tenant_id && $user->can('commission_settings.manage'));
    }
}
