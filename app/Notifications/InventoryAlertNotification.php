<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Daily digest of products at/under their reorder level and batches close to expiry.
 */
class InventoryAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Collection<int, array{product: string, branch: string, on_hand: int, reorder_level: int}>  $lowStock
     * @param  Collection<int, array{product: string, branch: string, batch: string, expiry_date: string, quantity: int, expired: bool}>  $expiring
     */
    public function __construct(
        public readonly Tenant $tenant,
        public readonly Collection $lowStock,
        public readonly Collection $expiring,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $parts = array_filter([
            $this->lowStock->isNotEmpty() ? $this->lowStock->count() . ' low stock' : null,
            $this->expiring->isNotEmpty() ? $this->expiring->count() . ' expiring' : null,
        ]);

        $message = (new MailMessage())
            ->subject("{$this->tenant->name}: inventory alert (" . implode(', ', $parts) . ')')
            ->greeting('Hello ' . ($notifiable->first_name ?: $notifiable->name) . ',');

        if ($this->lowStock->isNotEmpty()) {
            $message->line('**These products are at or below their reorder level:**');

            foreach ($this->lowStock as $row) {
                $message->line("• {$row['product']} at {$row['branch']}: {$row['on_hand']} on hand (reorder at {$row['reorder_level']})");
            }

            $message->action('Create purchase order', route('inventory.purchases.create'));
        }

        if ($this->expiring->isNotEmpty()) {
            $message->line('**These batches are expired or expiring soon:**');

            foreach ($this->expiring as $row) {
                $state = $row['expired'] ? 'EXPIRED' : 'expires';
                $message->line("• {$row['product']} (batch {$row['batch']}) at {$row['branch']}: {$row['quantity']} units, {$state} {$row['expiry_date']}");
            }

            $message->line('Review them under Inventory → Batches & Expiry: ' . route('inventory.batches.index', ['filter' => 'expiring']));
        }

        return $message->line('You receive this because you can view inventory for these branches.');
    }
}
