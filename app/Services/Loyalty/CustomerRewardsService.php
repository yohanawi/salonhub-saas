<?php

namespace App\Services\Loyalty;

use App\Models\Customer;
use App\Models\CustomerLoyaltyAccount;
use App\Models\CustomerMembership;
use App\Models\MessageLog;
use App\Models\User;
use App\Services\Messaging\MessagingService;
use App\Services\Settings\SettingService;
use Illuminate\Support\Facades\URL;

/**
 * What a customer can see about their own rewards: points balance by SMS/WhatsApp and a no-login
 * rewards page reached through a signed, expiring link.
 */
class CustomerRewardsService
{
    public function __construct(
        private readonly LoyaltyService $loyalty,
        private readonly LoyaltyTierService $tiers,
        private readonly MessagingService $messaging,
        private readonly SettingService $settings,
    ) {
    }

    public function account(Customer $customer): ?CustomerLoyaltyAccount
    {
        return CustomerLoyaltyAccount::withoutTenantScope()
            ->with(['tier', 'program'])
            ->where('tenant_id', $customer->tenant_id)
            ->where('customer_id', $customer->id)
            ->first();
    }

    public function pointsValue(?CustomerLoyaltyAccount $account): float
    {
        $program = $account?->program;

        if (! $program || $program->redemption_points <= 0) {
            return 0.0;
        }

        return round(((int) $account->available_points / $program->redemption_points) * (float) $program->redemption_value, 2);
    }

    public function portalUrl(Customer $customer): string
    {
        $days = (int) ($this->settings->get('loyalty.portal_link_days', $customer->tenant_id) ?: 30);

        return URL::temporarySignedRoute('rewards.show', now()->addDays($days), ['customer' => $customer->id]);
    }

    /**
     * Everything the rewards page shows.
     */
    public function summary(Customer $customer): array
    {
        $account = $this->account($customer);
        $next = $account ? $this->tiers->nextTier($account) : null;

        return [
            'customer' => $customer,
            'account' => $account,
            'pointsValue' => $this->pointsValue($account),
            'tier' => $account?->tier,
            'nextTier' => $next,
            'pointsToNext' => $next ? max(0, $next->min_points - (int) $account->lifetime_earned_points) : null,
            'tiers' => $account ? $this->tiers->tiersFor($account->loyalty_program_id) : collect(),
            'transactions' => $account ? $account->transactions()->latest()->limit(10)->get() : collect(),
            'membership' => CustomerMembership::withoutTenantScope()
                ->with('plan')
                ->where('tenant_id', $customer->tenant_id)
                ->where('customer_id', $customer->id)
                ->where('status', CustomerMembership::STATUS_ACTIVE)
                ->latest('end_date')
                ->first(),
        ];
    }

    public function sendBalance(Customer $customer, ?User $user = null): ?MessageLog
    {
        $customer->loadMissing('tenant');
        $account = $this->account($customer);

        if (! $account || ! $customer->phone) {
            return null;
        }

        $currency = $customer->tenant?->currency ?: 'LKR';
        $template = $this->settings->get('template.points_balance', $customer->tenant_id, $customer->branch_id);

        $body = $this->messaging->render($template, [
            'customer_name' => $customer->first_name ?: $customer->full_name,
            'points' => number_format((int) $account->available_points),
            'points_value' => $currency . ' ' . number_format($this->pointsValue($account), 2),
            'tier_line' => $account->tier ? " You are a {$account->tier->name} member." : '',
            'salon_name' => $customer->tenant?->name,
            'portal_url' => $this->portalUrl($customer),
        ]);

        return $this->messaging->send($customer->tenant_id, $customer->branch_id, 'points_balance', $customer->phone, $body, $customer, $user);
    }
}
