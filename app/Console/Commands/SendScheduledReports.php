<?php

namespace App\Console\Commands;

use App\Services\Reports\ScheduledReportService;
use Illuminate\Console\Command;

class SendScheduledReports extends Command
{
    protected $signature = 'reports:send-scheduled {--tenant_id=}';

    protected $description = 'Email scheduled reports that are due.';

    public function handle(ScheduledReportService $reports): int
    {
        $sent = $reports->sendDue($this->option('tenant_id') ? (int) $this->option('tenant_id') : null);

        $this->info("Sent {$sent} scheduled reports.");

        return self::SUCCESS;
    }
}
