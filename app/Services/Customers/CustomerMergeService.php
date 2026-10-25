<?php

namespace App\Services\Customers;

use App\Models\Customer;
use App\Models\CustomerLoyaltyAccount;
use App\Models\MessageLog;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Loyalty\LoyaltyTierService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Folds a duplicate customer into the record being kept: appointments, invoices, loyalty points, memberships,
 * packages, notes, photos and everything else that points at the duplicate moves over, and the duplicate is
 * archived with a pointer to the kept record.
 */
class CustomerMergeService
{
    // Profile fields copied from the duplicate only where the kept record has nothing.
    private const FILL_BLANK_FIELDS = ['email', 'phone', 'gender', 'date_of_birth', 'anniversary_date', 'address', 'branch_id'];

    // Free text from both records is kept rather than lost.
    private const APPEND_FIELDS = ['notes', 'allergies'];

    public function __construct(
        private readonly LoyaltyTierService $tiers,
        private readonly AuditLogService $audit,
    ) {
    }

    public function merge(Customer $keep, Customer $duplicate, User $user): Customer
    {
        if ($keep->is($duplicate) || (int) $keep->tenant_id !== (int) $duplicate->tenant_id) {
            throw ValidationException::withMessages(['duplicate_id' => 'Choose another customer from this salon.']);
        }

        return DB::transaction(function () use ($keep, $duplicate, $user) {
            // Locked in id order so two merges involving the same customers cannot deadlock.
            $locked = Customer::withoutTenantScope()->whereKey([$keep->id, $duplicate->id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $keep = $locked->get($keep->id);
            $duplicate = $locked->get($duplicate->id);

            if (! $keep || ! $duplicate || $duplicate->merged_into_customer_id || $keep->merged_into_customer_id) {
                throw ValidationException::withMessages(['duplicate_id' => 'One of these customers has already been merged.']);
            }

            $this->mergeLoyaltyAccounts($keep, $duplicate);

            // Targeted promotions: the kept customer may already be on the same promotion.
            DB::table('promotion_customers')
                ->where('customer_id', $duplicate->id)
                ->whereIn('promotion_id', DB::table('promotion_customers')->where('customer_id', $keep->id)->select('promotion_id'))
                ->delete();

            $moved = [];

            foreach ($this->referencingTables() as $table) {
                $count = DB::table($table)->where('customer_id', $duplicate->id)->update(['customer_id' => $keep->id]);

                if ($count > 0) {
                    $moved[$table] = $count;
                }
            }

            Customer::withoutTenantScope()->where('referred_by_customer_id', $duplicate->id)->whereKeyNot($keep->id)
                ->update(['referred_by_customer_id' => $keep->id]);

            MessageLog::withoutTenantScope()
                ->where('related_type', $duplicate->getMorphClass())
                ->where('related_id', $duplicate->id)
                ->update(['related_id' => $keep->id]);

            $this->mergeProfile($keep, $duplicate);

            $duplicate->forceFill([
                'status' => Customer::STATUS_INACTIVE,
                'merged_into_customer_id' => $keep->id,
                'merged_at' => now(),
                'referred_by_customer_id' => null,
            ])->save();
            $duplicate->delete();

            $this->audit->log([
                'tenant_id' => $keep->tenant_id,
                'user_id' => $user->id,
                'action' => 'customer.merged',
                'event' => 'customer.merged',
                'module' => 'customers',
                'description' => "Merged {$duplicate->full_name} ({$duplicate->customer_code}) into {$keep->full_name} ({$keep->customer_code}).",
                'auditable_type' => Customer::class,
                'auditable_id' => $keep->id,
                'metadata' => ['duplicate_id' => $duplicate->id, 'moved' => $moved],
            ], request());

            return $keep->fresh();
        });
    }

    private function mergeLoyaltyAccounts(Customer $keep, Customer $duplicate): void
    {
        $from = CustomerLoyaltyAccount::withoutTenantScope()->where('customer_id', $duplicate->id)->lockForUpdate()->first();
        $into = CustomerLoyaltyAccount::withoutTenantScope()->where('customer_id', $keep->id)->lockForUpdate()->first();

        // With only one account the generic customer_id move below carries it over.
        if (! $from || ! $into) {
            return;
        }

        DB::table('loyalty_point_transactions')
            ->where('loyalty_account_id', $from->id)
            ->update(['loyalty_account_id' => $into->id, 'customer_id' => $keep->id]);

        $into->forceFill([
            'available_points' => $into->available_points + $from->available_points,
            'pending_points' => $into->pending_points + $from->pending_points,
            'lifetime_earned_points' => $into->lifetime_earned_points + $from->lifetime_earned_points,
            'lifetime_redeemed_points' => $into->lifetime_redeemed_points + $from->lifetime_redeemed_points,
            'expired_points' => $into->expired_points + $from->expired_points,
            'joined_at' => collect([$into->joined_at, $from->joined_at])->filter()->min(),
            'last_activity_at' => collect([$into->last_activity_at, $from->last_activity_at])->filter()->max(),
        ])->save();

        $from->delete();

        $this->tiers->refresh($into->fresh());
    }

    private function mergeProfile(Customer $keep, Customer $duplicate): void
    {
        foreach (self::FILL_BLANK_FIELDS as $field) {
            if (blank($keep->{$field}) && filled($duplicate->{$field})) {
                $keep->{$field} = $duplicate->{$field};
            }
        }

        foreach (self::APPEND_FIELDS as $field) {
            if (filled($duplicate->{$field}) && $duplicate->{$field} !== $keep->{$field}) {
                $keep->{$field} = trim(($keep->{$field} ? $keep->{$field} . "\n" : '') . $duplicate->{$field});
            }
        }

        $keep->last_visit_at = collect([$keep->last_visit_at, $duplicate->last_visit_at])->filter()->max();

        if (! $keep->referred_by_customer_id && $duplicate->referred_by_customer_id && (int) $duplicate->referred_by_customer_id !== $keep->id) {
            $keep->referred_by_customer_id = $duplicate->referred_by_customer_id;
        }

        $keep->save();
    }

    /**
     * Every table with a customer_id column, so records added by future modules are moved too.
     *
     * @return array<int, string>
     */
    private function referencingTables(): array
    {
        return collect(Schema::getTables())
            ->pluck('name')
            ->unique()
            ->reject(fn (string $table) => $table === 'customers')
            ->filter(fn (string $table) => Schema::hasColumn($table, 'customer_id'))
            ->values()
            ->all();
    }
}
