<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Staff;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlanEntitlementService
{
    public const LIMITS = [
        'max_branches' => 'branches',
        'max_staff' => 'staff',
        'max_users' => 'users',
        'max_customers' => 'customers',
    ];

    public const FEATURE_MESSAGES = [
        'services' => 'Your subscription plan does not include service management.',
        'customer_management' => 'Your subscription plan does not include customer management.',
        'inventory' => 'Your subscription plan does not include inventory management.',
        'basic_reports' => 'Your subscription plan does not include reports.',
        'advanced_reports' => 'Your subscription plan does not include advanced reports.',
        'multi_branch_reports' => 'Your subscription plan does not include multi-branch reports.',
    ];

    public function assignPlan(Tenant $tenant, Plan $plan, ?string $status = null): Subscription
    {
        return DB::transaction(function () use ($tenant, $plan, $status) {
            $tenant = Tenant::query()->whereKey($tenant->getKey())->lockForUpdate()->firstOrFail();

            $this->ensurePlanCanCoverCurrentUsage($tenant, $plan);

            $trialEndsAt = ($plan->trial_days ?? 0) > 0 ? now()->addDays($plan->trial_days) : null;

            return Subscription::updateOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'plan_id' => $plan->id,
                    'status' => $status ?: ($trialEndsAt ? 'trialing' : 'active'),
                    'price' => $plan->price,
                    'billing_period' => $plan->billing_period,
                    'entitlements' => $plan->entitlementSnapshot(),
                    'trial_ends_at' => $trialEndsAt,
                    'starts_at' => now(),
                    'ends_at' => null,
                    'cancelled_at' => null,
                ]
            );
        });
    }

    public function ensurePlanCanCoverCurrentUsage(Tenant $tenant, Plan $plan): void
    {
        $usage = $this->usage($tenant);

        foreach (self::LIMITS as $limitKey => $usageKey) {
            $limit = $plan->{$limitKey};

            if ($limit !== null && $usage[$usageKey] > $limit) {
                throw ValidationException::withMessages([
                    $limitKey => "This plan allows {$limit} {$usageKey}, but the salon already has {$usage[$usageKey]}.",
                ]);
            }
        }
    }

    public function ensureCanCreate(Tenant $tenant, string $limitKey, ?string $message = null): void
    {
        $usageKey = self::LIMITS[$limitKey] ?? null;

        if (! $usageKey) {
            return;
        }

        $limit = $this->limit($tenant, $limitKey);

        if ($limit !== null && $this->usage($tenant)[$usageKey] >= $limit) {
            throw ValidationException::withMessages([
                str($usageKey)->singular()->toString() => $message ?: "Your subscription {$usageKey} limit has been reached.",
            ]);
        }
    }

    public function ensureFeature(Tenant $tenant, string $feature, ?string $message = null): void
    {
        if (! $this->featureEnabled($tenant, $feature)) {
            throw ValidationException::withMessages([
                'plan' => $message ?: (self::FEATURE_MESSAGES[$feature] ?? 'Your subscription plan does not include this feature.'),
            ]);
        }
    }

    public function featureEnabled(Tenant $tenant, string $feature): bool
    {
        $value = $this->featureValue($tenant, $feature);

        return ! in_array($value, [false, null, 'false', 0, '0'], true);
    }

    public function featureValue(Tenant $tenant, string $feature): mixed
    {
        $subscription = $this->subscription($tenant);

        return data_get($subscription?->entitlements, "features.{$feature}", $subscription?->plan?->featureValue($feature) ?? false);
    }

    public function limit(Tenant $tenant, string $limitKey): ?int
    {
        $subscription = $this->subscription($tenant);
        $limit = data_get($subscription?->entitlements, "limits.{$limitKey}", $subscription?->plan?->{$limitKey});

        return $limit === null ? null : (int) $limit;
    }

    public function usage(Tenant $tenant): array
    {
        return [
            'branches' => Branch::withoutGlobalScope('tenant')->withTrashed()->where('tenant_id', $tenant->id)->count(),
            'staff' => Staff::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->count(),
            'users' => User::query()->where('tenant_id', $tenant->id)->count(),
            'customers' => Customer::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->count(),
        ];
    }

    private function subscription(Tenant $tenant): ?Subscription
    {
        return $tenant->subscription()->with('plan')->first();
    }
}
