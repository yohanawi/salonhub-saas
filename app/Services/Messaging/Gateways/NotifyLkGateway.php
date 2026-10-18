<?php

namespace App\Services\Messaging\Gateways;

use Illuminate\Support\Facades\Http;

/**
 * Notify.lk SMS (https://developer.notify.lk). Needs the account's user ID, API key and an
 * approved sender ID ("NotifyDEMO" works for testing).
 */
class NotifyLkGateway implements MessageGateway
{
    public const ENDPOINT = 'https://app.notify.lk/api/v1/send';

    public function __construct(
        private readonly string $userId,
        private readonly string $apiKey,
        private readonly string $senderId
    ) {
    }

    public function send(string $to, string $body, array $context = []): GatewayResult
    {
        $response = Http::timeout(15)->get(self::ENDPOINT, [
            'user_id' => $this->userId,
            'api_key' => $this->apiKey,
            'sender_id' => $this->senderId,
            'to' => $to,
            'message' => $body,
        ]);

        if (! $response->successful()) {
            return GatewayResult::fromHttpError($response);
        }

        // Notify.lk answers 200 with {"status":"error",...} for account problems.
        if ($response->json('status') !== 'success') {
            return GatewayResult::failed((string) ($response->json('errors') ? json_encode($response->json('errors')) : $response->body()));
        }

        // The API returns no message id, only {"status":"success","data":"Sent"}.
        return GatewayResult::sent();
    }

    public function name(): string
    {
        return 'notify_lk';
    }
}
