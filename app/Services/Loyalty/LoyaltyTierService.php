<?php

namespace App\Services\Loyalty;

use App\Models\CustomerLoyaltyAccount;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyTier;
use Illuminate\Support\Collection;

/**
 * Tiers are earned on lifetime points, so redeeming or expiring points never drops a customer a level;
 * only reversals of earned points (refunds/voids) can move them down.
 */
class LoyaltyTierService
{
    /** @var array<int, Collection> */
    private array $tiers = [];

    public function tiersFor(int $programId): Collection
    {
        return $this->tiers[$programId] ??= LoyaltyTier::withoutTenantScope()
            ->where('loyalty_program_id', $programId)
            ->orderBy('min_points')
            ->get();
    }

    public function tierForPoints(int $programId, int $lifetimePoints): ?LoyaltyTier
    {
        return $this->tiersFor($programId)
            ->filter(fn (LoyaltyTier $tier) => $lifetimePoints >= $tier->min_points)
            ->last();
    }

    public function nextTier(CustomerLoyaltyAccount $account): ?LoyaltyTier
    {
        return $this->tiersFor($account->loyalty_program_id)
            ->first(fn (LoyaltyTier $tier) => $tier->min_points > (int) $account->lifetime_earned_points);
    }

    public function refresh(CustomerLoyaltyAccount $account): CustomerLoyaltyAccount
    {
        $tier = $this->tierForPoints($account->loyalty_program_id, (int) $account->lifetime_earned_points);

        if ((int) $account->loyalty_tier_id !== (int) $tier?->id) {
            $account->update([
                'loyalty_tier_id' => $tier?->id,
                'tier_achieved_at' => $tier ? now() : null,
            ]);
        }

        return $account;
    }

    /**
     * Re-places every member after the tier ladder was edited.
     */
    public function refreshProgram(LoyaltyProgram $program): int
    {
        unset($this->tiers[$program->id]);
        $changed = 0;

        CustomerLoyaltyAccount::withoutTenantScope()
            ->where('loyalty_program_id', $program->id)
            ->chunkById(500, function ($accounts) use (&$changed) {
                foreach ($accounts as $account) {
                    $before = $account->loyalty_tier_id;
                    $this->refresh($account);
                    $changed += (int) ($before !== $account->loyalty_tier_id);
                }
            });

        return $changed;
    }

    public function forget(): void
    {
        $this->tiers = [];
    }
}
