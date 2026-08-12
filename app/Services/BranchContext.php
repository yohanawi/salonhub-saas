<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class BranchContext
{
    public function current(?User $user = null): ?Branch
    {
        $user = $user ?: auth()->user();

        if (! $user || ! $user->tenant_id) {
            return null;
        }

        $branchId = Session::get('current_branch_id');

        if ($branchId) {
            $branch = Branch::query()
                ->where('tenant_id', $user->tenant_id)
                ->whereKey($branchId)
                ->first();

            if ($branch && $this->canAccess($user, $branch)) {
                return $branch;
            }

            Session::forget('current_branch_id');
        }

        return $this->defaultBranch($user);
    }

    public function defaultBranch(User $user): ?Branch
    {
        if ($this->hasTenantWideBranchAccess($user)) {
            return $user->tenant?->mainBranch()->first()
                ?: $user->tenant?->branches()->where('status', Branch::STATUS_ACTIVE)->first();
        }

        return $user->branches()
            ->where('branches.status', Branch::STATUS_ACTIVE)
            ->first();
    }

    public function availableBranches(User $user): Collection
    {
        if ($this->hasTenantWideBranchAccess($user)) {
            return $user->tenant?->branches()
                ->where('status', Branch::STATUS_ACTIVE)
                ->orderByDesc('is_main')
                ->orderBy('name')
                ->get() ?? collect();
        }

        return $user->branches()
            ->where('branches.status', Branch::STATUS_ACTIVE)
            ->orderBy('branches.name')
            ->get();
    }

    public function currentForOperation(?User $user = null): Branch
    {
        $branch = $this->current($user);

        abort_unless($branch && ! $branch->trashed() && $branch->status === Branch::STATUS_ACTIVE, 422, 'Select an active branch before continuing.');

        return $branch;
    }

    public function switch(User $user, Branch $branch): void
    {
        abort_unless($this->canAccess($user, $branch), 403);
        abort_if($branch->trashed() || $branch->status !== Branch::STATUS_ACTIVE, 422, 'Only active branches can be selected as the current branch.');

        Session::put('current_branch_id', $branch->id);
    }

    public function clear(): void
    {
        Session::forget('current_branch_id');
    }

    public function canAccess(User $user, Branch $branch): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ((int) $user->tenant_id !== (int) $branch->tenant_id) {
            return false;
        }

        if ($this->hasTenantWideBranchAccess($user)) {
            return true;
        }

        return $user->branches()->whereKey($branch->id)->exists();
    }

    public function hasTenantWideBranchAccess(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Salon Owner', 'Salon Admin']);
    }
}
