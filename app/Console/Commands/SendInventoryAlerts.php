<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Inventory\InventoryAlertService;
use Illuminate\Console\Command;

class SendInventoryAlerts extends Command
{
    protected $signature = 'inventory:send-alerts {--tenant_id=}';

    protected $description = 'Email low stock and expiring batch alerts to inventory users.';

    public function handle(InventoryAlertService $alerts): int
    {
        $tenants = Tenant::query()
            ->operational()
            ->when($this->option('tenant_id'), fn ($query) => $query->whereKey($this->option('tenant_id')))
            ->get();

        $lowStock = 0;
        $expiring = 0;

        foreach ($tenants as $tenant) {
            $result = $alerts->sendForTenant($tenant);
            $lowStock += $result['low_stock'];
            $expiring += $result['expiring'];
        }

        $this->info("Alerted {$lowStock} low stock items and {$expiring} expiring batches.");

        return self::SUCCESS;
    }
}
