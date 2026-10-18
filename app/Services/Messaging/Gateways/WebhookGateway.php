<?php

namespace App\Services\Messaging\Gateways;

use Illuminate\Support\Facades\Http;

/**
 * "Custom" provider: posts the message as JSON to the salon's own URL, for local SMS resellers or
 * a WhatsApp middleware. The API key, when set, is sent as a bearer token.
 */
class WebhookGateway implements MessageGateway
{
    public function __construct(
        private readonly string $url,
        private readonly ?string $apiKey,
        private readonly string $channel
    ) {
    }

    public function send(string $to, string $body, array $context = []): GatewayResult
    {
        $request = Http::acceptJson()->timeout(15);

        if (filled($this->apiKey)) {
            $request = $request->withToken($this->apiKey);
        }

        $response = $request->post($this->url, [
            'channel' => $this->channel,
            'to' => $to,
            'message' => $body,
            'event' => $context['event'] ?? null,
            'params' => $context['params'] ?? [],
        ]);

        return $response->successful()
            ? GatewayResult::sent((string) ($response->json('id') ?? $response->json('message_id') ?? '') ?: null)
            : GatewayResult::fromHttpError($response);
    }

    public function name(): string
    {
        return 'custom';
    }
}
