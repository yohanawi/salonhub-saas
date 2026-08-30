<?php

namespace App\Services\Promotions;

use App\Models\Promotion;
use App\Models\PromotionCoupon;

class PromotionApplicationResult
{
    public function __construct(
        public readonly bool $eligible,
        public readonly float $discount,
        public readonly string $message,
        public readonly ?Promotion $promotion = null,
        public readonly ?PromotionCoupon $coupon = null,
        public readonly float $eligibleSubtotal = 0,
        public readonly int $eligibleQuantity = 0,
    ) {
    }

    public function failed(): bool
    {
        return ! $this->eligible;
    }
}
