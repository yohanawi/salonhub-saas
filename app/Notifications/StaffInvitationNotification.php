<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Invites a staff member to set a password and sign in to their salon's SaloonHub account.
 */
class StaffInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly string $url,
        public readonly string $invitedBy,
        public readonly int $validDays,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("You're invited to {$this->tenant->name} on SaloonHub")
            ->greeting('Hello ' . ($notifiable->first_name ?: $notifiable->name) . ',')
            ->line("{$this->invitedBy} has invited you to sign in to {$this->tenant->name} on SaloonHub.")
            ->line('Choose a password to activate your account.')
            ->action('Accept invitation', $this->url)
            ->line("This link expires in {$this->validDays} days. If you weren't expecting it, you can ignore this email.");
    }
}
