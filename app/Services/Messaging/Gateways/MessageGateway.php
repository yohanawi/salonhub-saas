<?php

namespace App\Services\Messaging\Gateways;

interface MessageGateway
{
    /**
     * @param  string  $to  recipient in international digits, e.g. 94771234567
     * @param  array{event?: string, params?: array<int, string>}  $context  WhatsApp template data
     */
    public function send(string $to, string $body, array $context = []): GatewayResult;

    public function name(): string;
}
