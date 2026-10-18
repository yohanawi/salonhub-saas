<?php

namespace App\Services\Messaging;

use App\Jobs\SendCustomerMessage;
use App\Models\MessageLog;
use App\Models\User;
use App\Services\Messaging\Gateways\MessageGateway;
use App\Services\Messaging\Gateways\MetaWhatsAppGateway;
use App\Services\Messaging\Gateways\NotifyLkGateway;
use App\Services\Messaging\Gateways\TwilioGateway;
use App\Services\Messaging\Gateways\WebhookGateway;
use App\Services\Settings\SettingService;
use Illuminate\Database\Eloquent\Model;

/**
 * Sends customer SMS and WhatsApp messages through the provider the salon set up in Settings.
 * Every message is written to the message log first and sent by a queued job, so a slow or failing
 * provider never holds up the counter.
 */
class MessagingService
{
    public function __construct(private readonly SettingService $settings)
    {
    }

    /**
     * Channels to try, in order, for this salon/branch; empty when messaging is not set up.
     *
     * @return array<int, string>
     */
    public function channels(int $tenantId, ?int $branchId): array
    {
        $order = match ($this->settings->get('notification.channel', $tenantId, $branchId)) {
            'whatsapp' => [MessageLog::CHANNEL_WHATSAPP],
            'whatsapp_then_sms' => [MessageLog::CHANNEL_WHATSAPP, MessageLog::CHANNEL_SMS],
            default => [MessageLog::CHANNEL_SMS],
        };

        return array_values(array_filter($order, fn (string $channel) => $this->gateway($channel, $tenantId, $branchId) !== null));
    }

    public function enabled(int $tenantId, ?int $branchId): bool
    {
        return $this->channels($tenantId, $branchId) !== [];
    }

    /**
     * Logs the message and queues it. Returns null when there is no usable channel or number.
     *
     * @param  array<int, string>  $params  values for a WhatsApp template, in order
     */
    public function send(int $tenantId, ?int $branchId, string $event, ?string $phone, string $body, ?Model $related = null, ?User $user = null, array $params = []): ?MessageLog
    {
        $channels = $this->channels($tenantId, $branchId);
        $recipient = PhoneNumber::normalize($phone);

        if ($channels === [] || $recipient === null || trim($body) === '') {
            return null;
        }

        $log = MessageLog::create([
            'tenant_id' => $tenantId,
            'branch_id' => $branchId,
            'channel' => $channels[0],
            'event' => $event,
            'recipient' => $recipient,
            'body' => $body,
            'status' => MessageLog::STATUS_QUEUED,
            'related_type' => $related?->getMorphClass(),
            'related_id' => $related?->getKey(),
            'created_by' => $user?->id,
        ]);

        // After commit, so a sale that rolls back never texts the customer.
        SendCustomerMessage::dispatch($log->id, array_slice($channels, 1), $params)->afterCommit();

        return $log;
    }

    public function gateway(string $channel, int $tenantId, ?int $branchId): ?MessageGateway
    {
        $get = fn (string $key) => $this->settings->get($key, $tenantId, $branchId);

        if ($channel === MessageLog::CHANNEL_SMS) {
            if (! $get('notification.sms_enabled')) {
                return null;
            }

            return match ($get('integration.sms_provider')) {
                'notify_lk' => filled($get('integration.sms_user_id')) && filled($get('integration.sms_api_key'))
                    ? new NotifyLkGateway((string) $get('integration.sms_user_id'), (string) $get('integration.sms_api_key'), (string) ($get('integration.sms_sender_id') ?: 'NotifyDEMO'))
                    : null,
                'twilio' => filled($get('integration.sms_user_id')) && filled($get('integration.sms_api_key')) && filled($get('integration.sms_sender_id'))
                    ? new TwilioGateway((string) $get('integration.sms_user_id'), (string) $get('integration.sms_api_key'), (string) $get('integration.sms_sender_id'))
                    : null,
                'custom' => filled($get('integration.sms_custom_url'))
                    ? new WebhookGateway((string) $get('integration.sms_custom_url'), $get('integration.sms_api_key'), MessageLog::CHANNEL_SMS)
                    : null,
                default => null,
            };
        }

        if ($channel === MessageLog::CHANNEL_WHATSAPP) {
            if (! $get('notification.whatsapp_enabled')) {
                return null;
            }

            return match ($get('integration.whatsapp_provider')) {
                'meta' => filled($get('integration.whatsapp_access_token')) && filled($get('integration.whatsapp_phone_number_id'))
                    ? new MetaWhatsAppGateway(
                        (string) $get('integration.whatsapp_access_token'),
                        (string) $get('integration.whatsapp_phone_number_id'),
                        array_filter([
                            MessageLog::EVENT_RECEIPT => $get('integration.whatsapp_receipt_template'),
                            MessageLog::EVENT_BOOKING_CONFIRMATION => $get('integration.whatsapp_confirmation_template'),
                            MessageLog::EVENT_REMINDER => $get('integration.whatsapp_reminder_template'),
                        ]),
                        (string) ($get('integration.whatsapp_language') ?: 'en')
                    )
                    : null,
                'custom' => filled($get('integration.whatsapp_custom_url'))
                    ? new WebhookGateway((string) $get('integration.whatsapp_custom_url'), $get('integration.whatsapp_access_token'), MessageLog::CHANNEL_WHATSAPP)
                    : null,
                default => null,
            };
        }

        return null;
    }

    /**
     * Fills {placeholders}; unknown ones are left as typed so a template typo is visible.
     *
     * @param  array<string, scalar|null>  $values
     */
    public function render(?string $template, array $values): string
    {
        return trim(strtr((string) $template, collect($values)->mapWithKeys(fn ($value, $key) => ['{' . $key . '}' => (string) $value])->all()));
    }
}
