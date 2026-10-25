<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Membership\MembershipRenewalService;
use Illuminate\Console\Command;

class ProcessMembershipRenewals extends Command
{
    protected $signature = 'memberships:renew {--tenant_id=}';

    protected $description = 'Renew auto-renew memberships whose term has ended (raises an unpaid renewal invoice).';

    public function handle(MembershipRenewalService $renewals): int
    {
        $renewed = 0;
        $failed = 0;

        Tenant::query()
            ->operational()
            ->when($this->option('tenant_id'), fn ($query) => $query->whereKey($this->option('tenant_id')))
            ->each(function (Tenant $tenant) use ($renewals, &$renewed, &$failed) {
                $result = $renewals->renewDue($tenant);
                $renewed += $result['renewed'];
                $failed += $result['failed'];
            });

        $this->info("Renewed {$renewed} memberships ({$failed} could not be renewed).");

        return self::SUCCESS;
    }
}
