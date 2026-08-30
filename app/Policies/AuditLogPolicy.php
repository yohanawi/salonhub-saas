<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\BranchContext;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin')
            || ($user->tenant_id !== null && $user->hasAnyPermission([
                'audit-log.view',
                'audit-log.view-own',
                'audit-log.view-branch',
                'audit-log.view-all',
            ]));
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ((int) $user->tenant_id !== (int) $auditLog->tenant_id) {
            return false;
        }

        if ($user->can('audit-log.view-all')) {
            return true;
        }

        if ($user->can('audit-log.view-branch') && $auditLog->branch_id) {
            return app(BranchContext::class)
                ->availableBranches($user)
                ->pluck('id')
                ->contains((int) $auditLog->branch_id);
        }

        return $user->can('audit-log.view-own') && (int) $auditLog->user_id === (int) $user->id;
    }

    public function export(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('audit-log.export'));
    }

    public function viewSensitive(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->tenant_id !== null && $user->can('audit-log.view-sensitive'));
    }

    public function update(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    public function delete(User $user, AuditLog $auditLog): bool
    {
        return false;
    }
}
