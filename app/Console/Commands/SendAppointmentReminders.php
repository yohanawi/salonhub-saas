<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\Messaging\CustomerNotifier;
use App\Services\Messaging\MessagingService;
use App\Services\Settings\SettingService;
use Illuminate\Console\Command;

/**
 * Sends one reminder per upcoming appointment, "Booking Reminder Hours" before it starts.
 * Runs every few minutes from the scheduler; reminder_sent_at keeps it from repeating.
 */
class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';

    protected $description = 'Send SMS/WhatsApp reminders for upcoming appointments';

    // The longest reminder window the setting allows (30 days).
    private const MAX_HOURS = 720;

    public function handle(CustomerNotifier $notifier, MessagingService $messaging, SettingService $settings): int
    {
        $sent = 0;

        Appointment::withoutTenantScope()
            ->whereNull('reminder_sent_at')
            ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED])
            ->where('starts_at', '>', now())
            ->where('starts_at', '<=', now()->addHours(self::MAX_HOURS))
            ->with(['tenant', 'branch', 'customer'])
            ->chunkById(200, function ($appointments) use ($notifier, $messaging, $settings, &$sent) {
                foreach ($appointments as $appointment) {
                    $hours = (int) $settings->get('notification.booking_reminder_hours', $appointment->tenant_id, $appointment->branch_id);

                    if ($hours <= 0 || $appointment->starts_at->gt(now()->addHours($hours)) || ! $messaging->enabled($appointment->tenant_id, $appointment->branch_id)) {
                        continue;
                    }

                    $notifier->reminder($appointment);
                    // Marked even without a phone number, so it is not retried every run.
                    $appointment->forceFill(['reminder_sent_at' => now()])->saveQuietly();
                    $sent++;
                }
            });

        $this->info("Reminders processed: {$sent}");

        return self::SUCCESS;
    }
}
