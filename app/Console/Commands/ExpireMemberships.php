<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Membership\MembershipService;
use Illuminate\Console\Command;

class ExpireMemberships extends Command
{
    protected $signature = 'memberships:expire {--tenant_id=}';

    protected $description = 'Mark customer memberships as expired when validity has passed.';

    public function handle(MembershipService $memberships): int
    {
        $tenants = Tenant::query()
            ->when($this->option('tenant_id'), fn ($query) => $query->whereKey($this->option('tenant_id')))
            ->get();

        $expired = 0;

        foreach ($tenants as $tenant) {
            $expired += $memberships->expireMemberships($tenant);
        }

        $this->info("Expired {$expired} memberships.");

        return self::SUCCESS;
    }
}
