<?php

namespace App\Http\Requests\Billing\Concerns;

use App\Models\GiftCard;
use App\Models\PaymentMethod;
use App\Services\Billing\GiftCardService;
use Illuminate\Validation\Validator;

/**
 * Checks for tender lines that settle against stored value rather than money handed over: a gift
 * card must exist and cover what is charged to it, and deposits are never tendered by hand.
 */
trait ValidatesSystemTender
{
    /**
     * @param  array<string, float>  $giftCardTotals  running amount charged to each card in this request
     */
    protected function validateSystemTender(Validator $validator, PaymentMethod $method, array $line, string $methodField, string $codeField, int $tenantId, array &$giftCardTotals): void
    {
        if ($method->type === PaymentMethod::TYPE_DEPOSIT) {
            $validator->errors()->add($methodField, 'Deposits are applied automatically at checkout.');

            return;
        }

        if ($method->type !== PaymentMethod::TYPE_GIFT_CARD) {
            return;
        }

        $code = GiftCard::normalizeCode($line['gift_card_code'] ?? null);

        if ($code === '') {
            $validator->errors()->add($codeField, 'Enter the gift card code.');

            return;
        }

        // Two lines on the same card must fit its balance together.
        $giftCardTotals[$code] = ($giftCardTotals[$code] ?? 0) + (float) ($line['amount'] ?? 0);
        $giftCards = app(GiftCardService::class);

        if ($reason = $giftCards->unusableReason($giftCards->find($tenantId, $code), $giftCardTotals[$code])) {
            $validator->errors()->add($codeField, $reason);
        }
    }
}
