<?php

namespace App\Services\Loyalty;

use App\Models\Customer;
use App\Models\CustomerLoyaltyAccount;
use App\Models\CustomerMembership;
use App\Models\Invoice;
use App\Models\LoyaltyEarningRule;
use App\Models\LoyaltyPointTransaction;
use App\Models\LoyaltyProgram;
use App\Models\MembershipBenefit;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoyaltyService
{
    public function activeProgram(Tenant $tenant): ?LoyaltyProgram
    {
        return LoyaltyProgram::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('status', LoyaltyProgram::STATUS_ACTIVE)
            ->latest()
            ->first();
    }

    public function ensureDefaultProgram(Tenant $tenant, ?User $user = null): LoyaltyProgram
    {
        return LoyaltyProgram::withoutTenantScope()->firstOrCreate([
            'tenant_id' => $tenant->id,
            'status' => LoyaltyProgram::STATUS_ACTIVE,
        ], [
            'name' => 'Glow Rewards',
            'points_expiry_days' => 365,
            'minimum_redeem_points' => 100,
            'maximum_redeem_percentage' => 30,
            'allow_partial_redemption' => true,
            'allow_points_on_discounted_sales' => false,
            'redemption_points' => 100,
            'redemption_value' => 500,
            'created_by' => $user?->id,
        ]);
    }

    public function ensureDefaultRule(LoyaltyProgram $program): LoyaltyEarningRule
    {
        return LoyaltyEarningRule::withoutTenantScope()->firstOrCreate([
            'tenant_id' => $program->tenant_id,
            'loyalty_program_id' => $program->id,
            'rule_type' => LoyaltyEarningRule::TYPE_SPEND,
            'name' => 'Spend-based Points',
        ], [
            'spend_amount' => 100,
            'points_awarded' => 1,
            'status' => LoyaltyEarningRule::STATUS_ACTIVE,
            'priority' => 100,
        ]);
    }

    public function accountFor(Customer $customer, ?LoyaltyProgram $program = null): ?CustomerLoyaltyAccount
    {
        $program = $program ?: $this->activeProgram($customer->tenant);

        if (! $program || $customer->status !== Customer::STATUS_ACTIVE) {
            return null;
        }

        return CustomerLoyaltyAccount::withoutTenantScope()->firstOrCreate([
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
        ], [
            'loyalty_program_id' => $program->id,
            'status' => CustomerLoyaltyAccount::STATUS_ACTIVE,
            'joined_at' => now(),
            'last_activity_at' => now(),
        ]);
    }

    public function earnFromInvoice(Invoice $invoice, ?User $user = null): ?LoyaltyPointTransaction
    {
        $invoice->loadMissing(['tenant', 'customer', 'items']);

        if (! $invoice->customer || $invoice->payment_status !== Invoice::PAYMENT_PAID || $invoice->status !== Invoice::STATUS_ISSUED) {
            return null;
        }

        if (LoyaltyPointTransaction::withoutTenantScope()
            ->where('tenant_id', $invoice->tenant_id)
            ->where('type', LoyaltyPointTransaction::TYPE_EARN)
            ->where('source_type', Invoice::class)
            ->where('source_id', $invoice->id)
            ->exists()) {
            return null;
        }

        $program = $this->activeProgram($invoice->tenant);

        if (! $program) {
            return null;
        }

        $account = $this->accountFor($invoice->customer, $program);

        if (! $account) {
            return null;
        }

        $points = $this->calculateEarnedPoints($invoice, $program);

        if ($points <= 0) {
            return null;
        }

        $expiresAt = $program->points_expiry_days ? now()->addDays($program->points_expiry_days) : null;

        return $this->addPoints(
            $account,
            $points,
            LoyaltyPointTransaction::TYPE_EARN,
            $invoice,
            $user,
            'Points earned from invoice ' . $invoice->invoice_number,
            $expiresAt
        );
    }

    public function redeemForInvoice(Invoice $invoice, int $requestedPoints, User $user): array
    {
        if ($requestedPoints <= 0 || ! $invoice->customer) {
            return ['points' => 0, 'amount' => 0.0];
        }

        $program = $this->activeProgram($invoice->tenant);
        $account = $program ? $this->accountFor($invoice->customer, $program) : null;

        if (! $program || ! $account || $account->status !== CustomerLoyaltyAccount::STATUS_ACTIVE) {
            throw ValidationException::withMessages(['loyalty_points_to_redeem' => 'This customer does not have an active loyalty account.']);
        }

        if ($requestedPoints < $program->minimum_redeem_points) {
            throw ValidationException::withMessages(['loyalty_points_to_redeem' => "Redeem at least {$program->minimum_redeem_points} points."]);
        }

        $points = $requestedPoints;

        if (! $program->allow_partial_redemption) {
            $units = intdiv($requestedPoints, max(1, $program->redemption_points));
            $points = $units * $program->redemption_points;
        }

        if ($points <= 0) {
            throw ValidationException::withMessages(['loyalty_points_to_redeem' => 'Enter valid loyalty points to redeem.']);
        }

        $redemptionAmount = $this->pointsValue($program, $points);
        $maximumAmount = round(((float) $invoice->total) * ((float) $program->maximum_redeem_percentage / 100), 2);
        $redemptionAmount = min($redemptionAmount, $maximumAmount, (float) $invoice->total);
        $points = $this->pointsForValue($program, $redemptionAmount, $points);

        if ($redemptionAmount <= 0 || $points <= 0) {
            throw ValidationException::withMessages(['loyalty_points_to_redeem' => 'The requested points cannot be redeemed for this invoice.']);
        }

        return DB::transaction(function () use ($invoice, $account, $points, $redemptionAmount, $user) {
            $locked = CustomerLoyaltyAccount::withoutTenantScope()
                ->where('tenant_id', $account->tenant_id)
                ->whereKey($account->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->available_points < $points) {
                throw ValidationException::withMessages(['loyalty_points_to_redeem' => 'The customer does not have enough points.']);
            }

            $this->movePoints(
                $locked,
                -$points,
                LoyaltyPointTransaction::TYPE_REDEEM,
                $invoice,
                $user,
                'Points redeemed on invoice ' . $invoice->invoice_number
            );

            $invoice->update([
                'loyalty_account_id' => $locked->id,
                'loyalty_points_redeemed' => $points,
                'loyalty_redemption_amount' => $redemptionAmount,
                'discount' => (float) $invoice->discount + $redemptionAmount,
                'total' => max(0, (float) $invoice->total - $redemptionAmount),
                'balance_amount' => max(0, (float) $invoice->balance_amount - $redemptionAmount),
            ]);

            return ['points' => $points, 'amount' => $redemptionAmount];
        });
    }

    public function adjust(Customer $customer, int $points, string $reason, User $user): LoyaltyPointTransaction
    {
        if ($points === 0) {
            throw ValidationException::withMessages(['points' => 'Enter points greater or less than zero.']);
        }

        $program = $this->activeProgram($customer->tenant) ?: $this->ensureDefaultProgram($customer->tenant, $user);
        $account = $this->accountFor($customer, $program);

        return $this->movePoints(
            $account,
            $points,
            $points > 0 ? LoyaltyPointTransaction::TYPE_ADJUSTMENT_ADD : LoyaltyPointTransaction::TYPE_ADJUSTMENT_DEDUCT,
            null,
            $user,
            $reason
        );
    }

    public function reverseForInvoice(Invoice $invoice, User $user, ?string $reason = null): ?LoyaltyPointTransaction
    {
        $earned = LoyaltyPointTransaction::withoutTenantScope()
            ->where('tenant_id', $invoice->tenant_id)
            ->where('type', LoyaltyPointTransaction::TYPE_EARN)
            ->where('source_type', Invoice::class)
            ->where('source_id', $invoice->id)
            ->first();

        if (! $earned || LoyaltyPointTransaction::withoutTenantScope()
            ->where('tenant_id', $invoice->tenant_id)
            ->where('type', LoyaltyPointTransaction::TYPE_REFUND_REVERSAL)
            ->where('source_type', Invoice::class)
            ->where('source_id', $invoice->id)
            ->exists()) {
            return null;
        }

        $account = CustomerLoyaltyAccount::withoutTenantScope()->whereKey($earned->loyalty_account_id)->firstOrFail();
        $points = min((int) $earned->points, (int) $account->available_points);

        if ($points <= 0) {
            return null;
        }

        return $this->movePoints(
            $account,
            -$points,
            LoyaltyPointTransaction::TYPE_REFUND_REVERSAL,
            $invoice,
            $user,
            $reason ?: 'Points reversed for invoice ' . $invoice->invoice_number
        );
    }

    public function expirePoints(Tenant $tenant, ?User $user = null): int
    {
        $expired = 0;

        LoyaltyPointTransaction::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->whereIn('type', [LoyaltyPointTransaction::TYPE_EARN, LoyaltyPointTransaction::TYPE_BONUS, LoyaltyPointTransaction::TYPE_ADJUSTMENT_ADD])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get()
            ->each(function (LoyaltyPointTransaction $transaction) use (&$expired, $user) {
                $alreadyExpired = LoyaltyPointTransaction::withoutTenantScope()
                    ->where('type', LoyaltyPointTransaction::TYPE_EXPIRE)
                    ->where('source_type', LoyaltyPointTransaction::class)
                    ->where('source_id', $transaction->id)
                    ->exists();

                if ($alreadyExpired) {
                    return;
                }

                $account = CustomerLoyaltyAccount::withoutTenantScope()->whereKey($transaction->loyalty_account_id)->first();

                if (! $account || $account->available_points <= 0) {
                    return;
                }

                $points = min((int) $transaction->points, (int) $account->available_points);
                $this->movePoints($account, -$points, LoyaltyPointTransaction::TYPE_EXPIRE, $transaction, $user, 'Expired loyalty points');
                $expired += $points;
            });

        return $expired;
    }

    public function calculateEarnedPoints(Invoice $invoice, LoyaltyProgram $program): int
    {
        if (! $program->allow_points_on_discounted_sales && (float) $invoice->loyalty_redemption_amount > 0) {
            $eligibleTotal = max(0, (float) $invoice->total);
        } else {
            $eligibleTotal = max(0, (float) $invoice->total);
        }

        $points = 0;
        $rules = LoyaltyEarningRule::withoutTenantScope()
            ->where('tenant_id', $program->tenant_id)
            ->where('loyalty_program_id', $program->id)
            ->activeFor($invoice->paid_at ?: now())
            ->orderBy('priority')
            ->get();

        foreach ($rules as $rule) {
            $basis = $this->eligibleAmountForRule($invoice, $rule, $eligibleTotal);

            if ($basis < (float) $rule->minimum_purchase_amount) {
                continue;
            }

            $rulePoints = match ($rule->rule_type) {
                LoyaltyEarningRule::TYPE_BONUS => (int) $rule->points_awarded,
                default => (int) floor($basis / max(0.01, (float) $rule->spend_amount)) * (int) $rule->points_awarded,
            };

            if ($rule->maximum_points_per_transaction !== null) {
                $rulePoints = min($rulePoints, (int) $rule->maximum_points_per_transaction);
            }

            $points += max(0, $rulePoints);
        }

        return (int) floor($points * $this->membershipMultiplier($invoice));
    }

    public function addPoints(CustomerLoyaltyAccount $account, int $points, string $type, ?Model $source, ?User $user, ?string $description = null, mixed $expiresAt = null): LoyaltyPointTransaction
    {
        return $this->movePoints($account, abs($points), $type, $source, $user, $description, $expiresAt);
    }

    private function movePoints(CustomerLoyaltyAccount $account, int $points, string $type, ?Model $source, ?User $user, ?string $description = null, mixed $expiresAt = null): LoyaltyPointTransaction
    {
        return DB::transaction(function () use ($account, $points, $type, $source, $user, $description, $expiresAt) {
            $locked = CustomerLoyaltyAccount::withoutTenantScope()
                ->where('tenant_id', $account->tenant_id)
                ->whereKey($account->id)
                ->lockForUpdate()
                ->firstOrFail();

            $before = (int) $locked->available_points;
            $after = max(0, $before + $points);

            if ($points < 0 && $before + $points < 0 && ! in_array($type, [LoyaltyPointTransaction::TYPE_REFUND_REVERSAL, LoyaltyPointTransaction::TYPE_EXPIRE], true)) {
                throw ValidationException::withMessages(['points' => 'The customer does not have enough available points.']);
            }

            $transaction = LoyaltyPointTransaction::create([
                'tenant_id' => $locked->tenant_id,
                'customer_id' => $locked->customer_id,
                'loyalty_account_id' => $locked->id,
                'type' => $type,
                'points' => $points,
                'balance_before' => $before,
                'balance_after' => $after,
                'source_type' => $source ? $source::class : null,
                'source_id' => $source?->getKey(),
                'description' => $description,
                'earned_at' => in_array($type, [LoyaltyPointTransaction::TYPE_EARN, LoyaltyPointTransaction::TYPE_BONUS], true) ? now() : null,
                'expires_at' => $expiresAt ? Carbon::parse($expiresAt) : null,
                'created_by' => $user?->id,
            ]);

            $updates = [
                'available_points' => $after,
                'last_activity_at' => now(),
            ];

            if ($points > 0) {
                $updates['lifetime_earned_points'] = $locked->lifetime_earned_points + $points;
            } elseif ($type === LoyaltyPointTransaction::TYPE_REDEEM) {
                $updates['lifetime_redeemed_points'] = $locked->lifetime_redeemed_points + abs($points);
            } elseif ($type === LoyaltyPointTransaction::TYPE_EXPIRE) {
                $updates['expired_points'] = $locked->expired_points + abs($points);
            }

            $locked->update($updates);

            if ($type === LoyaltyPointTransaction::TYPE_EARN && $source instanceof Invoice) {
                $source->update(['loyalty_points_earned' => abs($points)]);
            }

            return $transaction;
        });
    }

    private function pointsValue(LoyaltyProgram $program, int $points): float
    {
        return round(($points / max(1, $program->redemption_points)) * (float) $program->redemption_value, 2);
    }

    private function pointsForValue(LoyaltyProgram $program, float $value, int $requestedPoints): int
    {
        $calculated = (int) ceil($value / max(0.01, (float) $program->redemption_value) * max(1, $program->redemption_points));

        return min($requestedPoints, $calculated);
    }

    private function eligibleAmountForRule(Invoice $invoice, LoyaltyEarningRule $rule, float $fallbackTotal): float
    {
        if (! $rule->service_id && ! $rule->service_category_id && ! $rule->product_id && ! $rule->branch_id) {
            return $fallbackTotal;
        }

        if ($rule->branch_id && (int) $invoice->branch_id !== (int) $rule->branch_id) {
            return 0.0;
        }

        $items = $invoice->items;

        if ($rule->service_id) {
            $items = $items->where('service_id', $rule->service_id);
        }

        if ($rule->product_id) {
            $items = $items->where('product_id', $rule->product_id);
        }

        if ($rule->service_category_id) {
            $items = $items->filter(fn ($item) => (int) ($item->service?->category_id ?? 0) === (int) $rule->service_category_id);
        }

        return (float) $items->sum('total_amount');
    }

    private function membershipMultiplier(Invoice $invoice): float
    {
        $membership = CustomerMembership::withoutTenantScope()
            ->with('plan.benefits')
            ->where('tenant_id', $invoice->tenant_id)
            ->where('customer_id', $invoice->customer_id)
            ->where('status', CustomerMembership::STATUS_ACTIVE)
            ->whereDate('start_date', '<=', $invoice->paid_at ?: today())
            ->whereDate('end_date', '>=', $invoice->paid_at ?: today())
            ->latest('end_date')
            ->first();

        if (! $membership) {
            return 1.0;
        }

        return max(1.0, (float) $membership->plan->benefits
            ->where('status', MembershipBenefit::STATUS_ACTIVE)
            ->where('benefit_type', MembershipBenefit::TYPE_BONUS_POINTS_MULTIPLIER)
            ->max('loyalty_multiplier'));
    }
}
