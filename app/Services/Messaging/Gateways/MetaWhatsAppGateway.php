<?php

namespace App\Services\Messaging\Gateways;

use Illuminate\Support\Facades\Http;

/**
 * WhatsApp Cloud API. Messages the salon starts (receipts, reminders) must use a template Meta has
 * approved; when a template name is configured for the event it is sent with the message values
 * as its body parameters, otherwise a plain text message is sent, which WhatsApp only delivers
 * within 24 hours of the customer's last message.
 */
class MetaWhatsAppGateway implements MessageGateway
{
    public const GRAPH_VERSION = 'v20.0';

    /**
     * @param  array<string, string>  $templates  event => approved template name
     */
    public function __construct(
        private readonly string $accessToken,
        private readonly string $phoneNumberId,
        private readonly array $templates = [],
        private readonly string $language = 'en'
    ) {
    }

    public function send(string $to, string $body, array $context = []): GatewayResult
    {
        $template = $this->templates[$context['event'] ?? ''] ?? null;

        $payload = $template
            ? [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => ['code' => $this->language],
                    'components' => [[
                        'type' => 'body',
                        'parameters' => collect($context['params'] ?? [])
                            ->map(fn ($value) => ['type' => 'text', 'text' => (string) $value])
                            ->values()
                            ->all(),
                    ]],
                ],
            ]
            : [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => ['body' => $body, 'preview_url' => true],
            ];

        $response = Http::withToken($this->accessToken)
            ->timeout(15)
            ->post('https://graph.facebook.com/' . self::GRAPH_VERSION . "/{$this->phoneNumberId}/messages", $payload);

        return $response->successful()
            ? GatewayResult::sent($response->json('messages.0.id'))
            : GatewayResult::fromHttpError($response);
    }

    public function name(): string
    {
        return 'meta';
    }
}
