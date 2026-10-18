<?php

namespace App\Jobs;

use App\Models\MessageLog;
use App\Services\Messaging\Gateways\GatewayResult;
use App\Services\Messaging\MessagingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Sends one logged message. When it fails and the salon set a fallback channel (WhatsApp, then
 * SMS), a new log row is written for the next channel and sent too.
 */
class SendCustomerMessage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    /**
     * @param  array<int, string>  $fallbackChannels
     * @param  array<int, string>  $params
     */
    public function __construct(
        public readonly int $messageLogId,
        public readonly array $fallbackChannels = [],
        public readonly array $params = []
    ) {
    }

    public function handle(MessagingService $messaging): void
    {
        $log = MessageLog::withoutTenantScope()->find($this->messageLogId);

        if (! $log || $log->status === MessageLog::STATUS_SENT) {
            return;
        }

        $gateway = $messaging->gateway($log->channel, $log->tenant_id, $log->branch_id);

        try {
            $result = $gateway
                ? $gateway->send($log->recipient, $log->body, ['event' => $log->event, 'params' => $this->params])
                : GatewayResult::failed(ucfirst($log->channel) . ' is no longer set up for this salon.');
        } catch (Throwable $exception) {
            // A provider outage must not surface as an error at the counter.
            report($exception);
            $result = GatewayResult::failed($exception->getMessage());
        }

        $log->update([
            'provider' => $gateway?->name(),
            'status' => $result->ok ? MessageLog::STATUS_SENT : MessageLog::STATUS_FAILED,
            'provider_message_id' => $result->messageId,
            'error' => $result->error,
            'attempts' => $log->attempts + 1,
            'sent_at' => $result->ok ? now() : null,
        ]);

        if (! $result->ok && $this->fallbackChannels !== []) {
            $next = $log->replicate(['provider', 'provider_message_id', 'error', 'sent_at', 'attempts']);
            $next->fill(['channel' => $this->fallbackChannels[0], 'status' => MessageLog::STATUS_QUEUED, 'attempts' => 0])->save();

            self::dispatch($next->id, array_slice($this->fallbackChannels, 1), $this->params);
        }
    }
}
