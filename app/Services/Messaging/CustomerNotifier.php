<?php

namespace App\Services\Messaging;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\MessageLog;
use App\Models\User;
use App\Services\Settings\SettingService;
use Illuminate\Support\Facades\URL;

/**
 * The customer messages the salon sends: payment receipts, booking confirmations and reminders.
 * Each honours its own on/off setting; nothing is sent when messaging is not set up.
 */
class CustomerNotifier
{
    public const RECEIPT_LINK_DAYS = 60;

    public function __construct(
        private readonly MessagingService $messaging,
        private readonly SettingService $settings
    ) {
    }

    /**
     * @param  bool  $manual  sent from the invoice page, so the automatic-receipt setting does not apply
     */
    public function invoiceReceipt(Invoice $invoice, ?User $user = null, ?string $phone = null, bool $manual = false): ?MessageLog
    {
        if (! $this->messaging->enabled($invoice->tenant_id, $invoice->branch_id)) {
            return null;
        }

        $invoice->loadMissing(['tenant', 'branch', 'customer']);
        $phone ??= $invoice->customer?->phone;

        if (! $manual && ! $this->setting('notification.send_receipts', $invoice->tenant_id, $invoice->branch_id)) {
            return null;
        }

        $values = [
            'customer_name' => $invoice->customer?->first_name ?: 'Customer',
            'salon_name' => $invoice->tenant?->name,
            'invoice_number' => $invoice->invoice_number,
            'amount' => 'LKR ' . number_format($invoice->amountDue(), 2),
            'receipt_url' => $this->receiptUrl($invoice),
            'salon_phone' => $invoice->branch?->phone,
        ];

        return $this->messaging->send(
            $invoice->tenant_id,
            $invoice->branch_id,
            MessageLog::EVENT_RECEIPT,
            $phone,
            $this->messaging->render($this->settings->get('template.payment_receipt', $invoice->tenant_id, $invoice->branch_id), $values),
            $invoice,
            $user,
            [$values['customer_name'], $values['invoice_number'], $values['amount'], $values['receipt_url']]
        );
    }

    public function bookingConfirmation(Appointment $appointment, ?User $user = null): ?MessageLog
    {
        if (! $this->setting('notification.send_booking_confirmation', $appointment->tenant_id, $appointment->branch_id)) {
            return null;
        }

        return $this->appointmentMessage($appointment, MessageLog::EVENT_BOOKING_CONFIRMATION, 'template.booking_confirmation', $user);
    }

    public function reminder(Appointment $appointment): ?MessageLog
    {
        if (! $this->setting('notification.send_reminders', $appointment->tenant_id, $appointment->branch_id)) {
            return null;
        }

        return $this->appointmentMessage($appointment, MessageLog::EVENT_REMINDER, 'template.booking_reminder', null);
    }

    /**
     * Customer-facing link to the 80mm receipt that needs no login and expires.
     */
    public function receiptUrl(Invoice $invoice): string
    {
        return URL::temporarySignedRoute('receipts.show', now()->addDays(self::RECEIPT_LINK_DAYS), ['invoice' => $invoice->id]);
    }

    private function appointmentMessage(Appointment $appointment, string $event, string $templateKey, ?User $user): ?MessageLog
    {
        if (! $this->messaging->enabled($appointment->tenant_id, $appointment->branch_id)) {
            return null;
        }

        $appointment->loadMissing(['tenant', 'branch', 'customer']);
        $timezone = $appointment->branch?->timezone ?: ($appointment->tenant?->timezone ?: config('app.timezone'));
        $startsAt = $appointment->starts_at?->copy()->setTimezone($timezone);

        $values = [
            'customer_name' => $appointment->customer?->first_name ?: 'Customer',
            'salon_name' => $appointment->tenant?->name,
            'branch_name' => $appointment->branch?->name,
            'appointment_date' => $startsAt?->format('D, M j'),
            'appointment_time' => $startsAt?->format('g:i A'),
            'appointment_number' => $appointment->appointment_number,
            'salon_phone' => $appointment->branch?->phone,
        ];

        return $this->messaging->send(
            $appointment->tenant_id,
            $appointment->branch_id,
            $event,
            $appointment->customer?->phone,
            $this->messaging->render($this->settings->get($templateKey, $appointment->tenant_id, $appointment->branch_id), $values),
            $appointment,
            $user,
            [$values['customer_name'], $values['salon_name'], $values['appointment_date'], $values['appointment_time']]
        );
    }

    private function setting(string $key, int $tenantId, ?int $branchId): bool
    {
        return (bool) $this->settings->get($key, $tenantId, $branchId);
    }
}
