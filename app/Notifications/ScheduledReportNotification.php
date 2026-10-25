<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A scheduled report email. The file is rendered before queueing (base64) so the queued job does not
 * need to rebuild the report.
 */
class ScheduledReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, string>  $summary
     */
    public function __construct(
        public readonly string $scheduleName,
        public readonly string $reportTitle,
        public readonly string $salonName,
        public readonly string $period,
        public readonly string $content,
        public readonly string $filename,
        public readonly string $mime,
        public readonly array $summary = [],
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage())
            ->subject("{$this->salonName}: {$this->reportTitle} ({$this->period})")
            ->greeting($this->reportTitle)
            ->line("{$this->salonName} · {$this->period}");

        foreach (array_slice($this->summary, 0, 6, true) as $label => $value) {
            $message->line("**{$label}:** {$value}");
        }

        return $message
            ->line("The full report is attached ({$this->filename}).")
            ->line("You receive this because you are on the \"{$this->scheduleName}\" schedule.")
            ->attachData(base64_decode($this->content), $this->filename, ['mime' => $this->mime]);
    }
}
