<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AuditLogFeedService
{
    public function __construct(private readonly BranchContext $branchContext)
    {
    }

    public function recentFor(User $user, int $limit = 15): Collection
    {
        return $this->visibleQuery($user)
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    public function todayCountFor(User $user): int
    {
        return $this->visibleQuery($user)
            ->whereDate('created_at', today())
            ->count();
    }

    private function visibleQuery(User $user): Builder
    {
        $query = AuditLog::withoutTenantScope()->with(['tenant', 'branch', 'user']);

        if ($user->hasRole('Super Admin')) {
            return $query;
        }

        $query->where('tenant_id', $user->tenant_id);

        if ($user->can('audit-log.view-all')) {
            return $query;
        }

        if ($user->can('audit-log.view-branch')) {
            $branchIds = $this->branchContext->availableBranches($user)->pluck('id')->all();

            return $query->where(function (Builder $query) use ($branchIds, $user) {
                $query->whereIn('branch_id', $branchIds)
                    ->orWhere('user_id', $user->id);
            });
        }

        return $query->where('user_id', $user->id);
    }
}
