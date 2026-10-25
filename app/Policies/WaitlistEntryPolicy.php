<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Services\BranchContext;

/**
 * The waitlist is part of booking: seeing it follows appointments.view, managing it appointments.create.
 */
class WaitlistEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null && $user->can('appointments.view');
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null && $user->can('appointments.create');
    }

    public function update(User $user, WaitlistEntry $entry): bool
    {
        if ((int) $user->tenant_id !== (int) $entry->tenant_id || ! $user->can('appointments.create')) {
            return false;
        }

        $branch = Branch::withoutTenantScope()->find($entry->branch_id);

        return $branch && app(BranchContext::class)->canAccess($user, $branch);
    }
}
