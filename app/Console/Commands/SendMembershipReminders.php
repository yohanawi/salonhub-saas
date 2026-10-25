<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Membership\MembershipRenewalService;
use Illuminate\Console\Command;

class SendMembershipReminders extends Command
{
    protected $signature = 'memberships:send-reminders {--tenant_id=}';

    protected $description = 'Text members whose membership is about to end.';

    public function handle(MembershipRenewalService $renewals): int
    {
        $sent = 0;

        Tenant::query()
            ->operational()
            ->when($this->option('tenant_id'), fn ($query) => $query->whereKey($this->option('tenant_id')))
            ->each(function (Tenant $tenant) use ($renewals, &$sent) {
                $sent += $renewals->sendReminders($tenant);
            });

        $this->info("Sent {$sent} membership reminders.");

        return self::SUCCESS;
    }
}
