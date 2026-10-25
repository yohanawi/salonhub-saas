<?php

namespace App\Console\Commands;

use App\Services\Appointment\AutoNoShowService;
use Illuminate\Console\Command;

class MarkNoShows extends Command
{
    protected $signature = 'appointments:mark-no-shows {--tenant_id=}';

    protected $description = 'Mark bookings as no-shows once the customer is past the grace period (salons with "Auto Mark No-show" on).';

    public function handle(AutoNoShowService $noShows): int
    {
        $marked = $noShows->run($this->option('tenant_id') ? (int) $this->option('tenant_id') : null);

        $this->info("Marked {$marked} appointments as no-shows.");

        return self::SUCCESS;
    }
}
