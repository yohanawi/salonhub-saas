<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Loyalty\LoyaltyService;
use Illuminate\Console\Command;

class ExpireLoyaltyPoints extends Command
{
    protected $signature = 'loyalty:expire-points {--tenant_id=}';

    protected $description = 'Expire loyalty points that passed their expiry date.';

    public function handle(LoyaltyService $loyalty): int
    {
        $tenants = Tenant::query()
            ->when($this->option('tenant_id'), fn ($query) => $query->whereKey($this->option('tenant_id')))
            ->get();

        $expired = 0;

        foreach ($tenants as $tenant) {
            $expired += $loyalty->expirePoints($tenant);
        }

        $this->info("Expired {$expired} loyalty points.");

        return self::SUCCESS;
    }
}
