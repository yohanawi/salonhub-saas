<?php

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

class SettingPolicy
{
    private const SECTION_PERMISSIONS = [
        'general' => 'settings.general.manage',
        'appointments' => 'settings.booking.manage',
        'sales' => 'settings.pos.manage',
        'team' => 'settings.payroll.manage',
        'inventory' => 'settings.inventory.manage',
        'customers' => 'settings.customer.manage',
        'communications' => 'settings.notification.manage',
        'security' => 'settings.security.manage',
        'integrations' => 'settings.integration.manage',
    ];

    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('settings.view'));
    }

    public function manage(User $user, ?string $section = null): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->tenant_id === null) {
            return false;
        }

        if ($section === null) {
            return collect(self::SECTION_PERMISSIONS)->contains(fn (string $permission) => $user->can($permission));
        }

        $permission = self::SECTION_PERMISSIONS[$section] ?? null;

        return $permission !== null && $user->can($permission);
    }

    public function update(User $user, Setting $setting): bool
    {
        return $user->hasRole('Super Admin')
            || ((int) $user->tenant_id === (int) $setting->tenant_id && $this->manage($user, null));
    }
}
