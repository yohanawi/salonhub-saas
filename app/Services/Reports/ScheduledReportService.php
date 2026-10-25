<?php

namespace App\Services\Reports;

use App\Models\Branch;
use App\Models\ReportSchedule;
use App\Notifications\ScheduledReportNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

class ScheduledReportService
{
    public function __construct(
        private readonly ReportCatalog $catalog,
        private readonly ReportExporter $exporter,
    ) {
    }

    /**
     * Sends every active schedule whose time has come. Returns how many were sent.
     */
    public function sendDue(?int $tenantId = null): int
    {
        $sent = 0;

        ReportSchedule::withoutTenantScope()
            ->with(['tenant', 'branch'])
            ->where('is_active', true)
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->get()
            ->filter(function (ReportSchedule $schedule) {
                $now = CarbonImmutable::now($schedule->tenant?->timezone ?: config('app.timezone'));

                return $schedule->next_run_at && CarbonImmutable::parse($schedule->next_run_at->format('Y-m-d H:i:s'), $now->getTimezone())->lessThanOrEqualTo($now);
            })
            ->each(function (ReportSchedule $schedule) use (&$sent) {
                $this->send($schedule);
                $sent++;
            });

        return $sent;
    }

    public function send(ReportSchedule $schedule, ?CarbonImmutable $runAt = null): void
    {
        $tenant = $schedule->tenant;
        $timezone = $tenant->timezone ?: config('app.timezone');
        $runAt ??= CarbonImmutable::now($timezone);
        [$from, $to] = $schedule->periodFor($runAt);

        $branches = Branch::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('status', Branch::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        $document = $this->catalog->document($schedule->report, $tenant, $branches, $schedule->branch, $from, $to);
        $content = $this->exporter->render($document, $schedule->format);

        Notification::route('mail', $schedule->recipients)->notify(new ScheduledReportNotification(
            $schedule->name,
            $document->title,
            $tenant->name,
            $from->format('M d, Y') . ($from->isSameDay($to) ? '' : ' – ' . $to->format('M d, Y')),
            base64_encode($content),
            $document->filename($schedule->format),
            $this->exporter->mime($schedule->format),
            $document->summary,
        ));

        $schedule->update([
            'last_sent_at' => $runAt->toDateTimeString(),
            'next_run_at' => $schedule->computeNextRun($runAt)->toDateTimeString(),
        ]);
    }
}
