<?php

namespace App\Services\Messaging\Gateways;

use Illuminate\Http\Client\Response;

final class GatewayResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly ?string $messageId = null,
        public readonly ?string $error = null
    ) {
    }

    public static function sent(?string $messageId = null): self
    {
        return new self(true, $messageId);
    }

    public static function failed(string $error): self
    {
        return new self(false, null, $error);
    }

    public static function fromHttpError(Response $response): self
    {
        return self::failed('HTTP ' . $response->status() . ': ' . mb_substr($response->body(), 0, 500));
    }
}
