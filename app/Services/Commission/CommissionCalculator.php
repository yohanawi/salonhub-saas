<?php

namespace App\Services\Commission;

use App\Models\CommissionSetting;

class CommissionCalculator
{
    public function calculate(float $grossAmount, float $discountAmount, array $rule): array
    {
        $grossCents = $this->toCents($grossAmount);
        $discountCents = min($grossCents, $this->toCents($discountAmount));
        $netCents = max(0, $grossCents - $discountCents);

        $baseCents = ($rule['calculate_on'] ?? CommissionSetting::BASIS_NET_AFTER_DISCOUNT) === CommissionSetting::BASIS_GROSS
            ? $grossCents
            : $netCents;

        $commissionCents = match ($rule['commission_type']) {
            CommissionSetting::TYPE_FIXED => $this->toCents((float) $rule['commission_value']),
            CommissionSetting::TYPE_NONE => 0,
            default => (int) round($baseCents * ((float) $rule['commission_value'] / 100)),
        };

        return [
            'gross_amount' => $this->toDecimal($grossCents),
            'discount_amount' => $this->toDecimal($discountCents),
            'net_amount' => $this->toDecimal($netCents),
            'commission_base' => $this->toDecimal($baseCents),
            'commission_amount' => $this->toDecimal(max(0, $commissionCents)),
        ];
    }

    private function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    private function toDecimal(int $cents): float
    {
        return round($cents / 100, 2);
    }
}
