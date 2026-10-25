<?php

namespace App\Console\Commands;

use App\Services\Appointment\WaitlistService;
use Illuminate\Console\Command;

class ExpireWaitlistEntries extends Command
{
    protected $signature = 'waitlist:expire';

    protected $description = 'Mark waitlist entries for days that have passed as expired.';

    public function handle(WaitlistService $waitlist): int
    {
        $expired = $waitlist->expirePast();

        $this->info("Expired {$expired} waitlist entries.");

        return self::SUCCESS;
    }
}
