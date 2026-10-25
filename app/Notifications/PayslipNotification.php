<?php

namespace App\Notifications;

use App\Models\PayrollItem;
use App\Services\Payroll\PayslipService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayslipNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly PayrollItem $item)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payslips = app(PayslipService::class);
        $item = $this->item->loadMissing(['tenant', 'staff', 'period']);
        $currency = $item->tenant?->currency ?: 'LKR';

        // The PDF is built when the mail is sent, so a queued job always attaches the current figures.
        return (new MailMessage())
            ->subject("{$item->tenant?->name}: your payslip for " . ($item->period?->name ?? $item->period?->end_date?->format('F Y')))
            ->greeting('Hello ' . ($item->staff?->first_name ?: 'there') . ',')
            ->line('Your payslip is attached.')
            ->line("Net pay: {$currency} " . number_format((float) $item->net_pay, 2))
            ->attachData($payslips->pdf($item), $payslips->filename($item), ['mime' => 'application/pdf']);
    }
}
