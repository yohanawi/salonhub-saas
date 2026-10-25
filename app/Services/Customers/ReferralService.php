<?php

namespace App\Services\Customers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\LoyaltyPointTransaction;
use App\Models\User;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Settings\SettingService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Refer-a-friend: every customer has a code; a new customer can be linked to whoever referred them.
 * When the new customer's first invoice is paid, both receive loyalty points (once).
 */
class ReferralService
{
    public function __construct(
        private readonly LoyaltyService $loyalty,
        private readonly SettingService $settings,
    ) {
    }

    public function codeFor(Customer $customer): string
    {
        if ($customer->referral_code) {
            return $customer->referral_code;
        }

        $base = strtoupper(Str::of($customer->first_name)->ascii()->replaceMatches('/[^A-Za-z]/', '')->substr(0, 5)) ?: 'FRIEND';

        do {
            $code = $base . random_int(100, 999);
        } while (Customer::withoutTenantScope()->where('tenant_id', $customer->tenant_id)->where('referral_code', $code)->exists());

        $customer->forceFill(['referral_code' => $code])->saveQuietly();

        return $code;
    }

    /**
     * Finds the referrer from a code or a customer id, refusing self-referral and other salons.
     */
    public function resolveReferrer(int $tenantId, ?string $code, ?int $customerId, ?Customer $newCustomer = null): ?Customer
    {
        if (blank($code) && ! $customerId) {
            return null;
        }

        $referrer = Customer::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->when(filled($code), fn ($query) => $query->whereRaw('UPPER(referral_code) = ?', [strtoupper(trim((string) $code))]))
            ->when(! filled($code), fn ($query) => $query->whereKey($customerId))
            ->first();

        if (! $referrer) {
            throw ValidationException::withMessages(['referral_code' => 'That referral code was not found.']);
        }

        if ($newCustomer && (int) $referrer->id === (int) $newCustomer->id) {
            throw ValidationException::withMessages(['referral_code' => 'A customer cannot refer themselves.']);
        }

        return $referrer;
    }

    /**
     * Called after an invoice is paid. Returns true when rewards were given.
     */
    public function rewardIfDue(Invoice $invoice, ?User $user = null): bool
    {
        $customer = $invoice->customer;

        if (! $customer?->referred_by_customer_id || $customer->referral_rewarded_at
            || $invoice->payment_status !== Invoice::PAYMENT_PAID
            || ! $this->settings->get('loyalty.referral_enabled', $invoice->tenant_id)) {
            return false;
        }

        $referrer = Customer::withoutTenantScope()->find($customer->referred_by_customer_id);
        $program = $this->loyalty->activeProgram($invoice->tenant);

        if (! $referrer || ! $program) {
            return false;
        }

        $referrerPoints = (int) ($this->settings->get('loyalty.referrer_points', $invoice->tenant_id) ?? 0);
        $refereePoints = (int) ($this->settings->get('loyalty.referee_points', $invoice->tenant_id) ?? 0);

        // Claim the reward first so a retried payment can never pay it twice.
        $claimed = Customer::withoutTenantScope()->whereKey($customer->id)->whereNull('referral_rewarded_at')->update(['referral_rewarded_at' => now()]);

        if (! $claimed) {
            return false;
        }

        if ($referrerPoints > 0 && ($account = $this->loyalty->accountFor($referrer, $program))) {
            $this->loyalty->addPoints($account, $referrerPoints, LoyaltyPointTransaction::TYPE_BONUS, $customer, $user, "Referral reward: {$customer->full_name} joined");
        }

        if ($refereePoints > 0 && ($account = $this->loyalty->accountFor($customer, $program))) {
            $this->loyalty->addPoints($account, $refereePoints, LoyaltyPointTransaction::TYPE_BONUS, $referrer, $user, "Welcome bonus: referred by {$referrer->full_name}");
        }

        return true;
    }
}
