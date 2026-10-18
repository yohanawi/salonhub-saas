<?php

namespace App\Services\Messaging\Gateways;

use Illuminate\Support\Facades\Http;

/**
 * Twilio Programmable SMS. The account SID and auth token authenticate; "from" is a Twilio number
 * or an approved alphanumeric sender.
 */
class TwilioGateway implements MessageGateway
{
    public function __construct(
        private readonly string $accountSid,
        private readonly string $authToken,
        private readonly string $from
    ) {
    }

    public function send(string $to, string $body, array $context = []): GatewayResult
    {
        $response = Http::asForm()
            ->withBasicAuth($this->accountSid, $this->authToken)
            ->timeout(15)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json", [
                'To' => '+' . $to,
                'From' => $this->from,
                'Body' => $body,
            ]);

        return $response->successful()
            ? GatewayResult::sent($response->json('sid'))
            : GatewayResult::fromHttpError($response);
    }

    public function name(): string
    {
        return 'twilio';
    }
}
