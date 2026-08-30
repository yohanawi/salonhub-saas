<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $guarded = [];

    public const BILLING_PERIODS = [
        'trial' => 'Trial',
        'monthly' => 'Monthly',
        'yearly' => 'Yearly',
    ];

    public const FEATURE_OPTIONS = [
        'customer_management' => 'Customer Management',
        'services' => 'Services',
        'appointment_calendar' => 'Appointment Calendar',
        'appointments_unlimited' => 'Unlimited Appointments',
        'pos_billing' => 'POS & Billing',
        'basic_reports' => 'Basic Reports',
        'inventory' => 'Inventory',
        'expenses' => 'Expenses',
        'staff_commissions' => 'Staff Commissions',
        'payroll' => 'Payroll',
        'loyalty_membership' => 'Loyalty & Membership',
        'promotions_discounts' => 'Promotions & Discounts',
        'advanced_reports' => 'Advanced Reports',
        'multi_branch_reports' => 'Multi-Branch Reports',
        'audit_logs' => 'Audit Logs',
        'priority_support' => 'Priority Support',
        'no_credit_card_required' => 'No Credit Card Required',
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'is_recommended' => 'boolean',
        'price' => 'decimal:2',
        'trial_days' => 'integer',
        'sort_order' => 'integer',
        'max_branches' => 'integer',
        'max_staff' => 'integer',
        'max_users' => 'integer',
        'max_customers' => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function featureValue(string $feature): mixed
    {
        return $this->features[$feature] ?? false;
    }

    public function hasFeature(string $feature): bool
    {
        return ! in_array($this->featureValue($feature), [false, null, 'false', 0, '0'], true);
    }

    public function enabledFeaturesCount(): int
    {
        return collect($this->features ?? [])
            ->filter(fn ($value) => ! in_array($value, [false, null, 'false', 0, '0'], true))
            ->count();
    }

    public function entitlementSnapshot(): array
    {
        return [
            'plan_id' => $this->id,
            'plan_name' => $this->name,
            'plan_slug' => $this->slug,
            'price' => (float) $this->price,
            'billing_period' => $this->billing_period,
            'trial_days' => $this->trial_days ?? 0,
            'limits' => [
                'max_branches' => $this->max_branches,
                'max_staff' => $this->max_staff,
                'max_users' => $this->max_users,
                'max_customers' => $this->max_customers,
            ],
            'features' => collect(self::FEATURE_OPTIONS)
                ->keys()
                ->mapWithKeys(fn (string $feature) => [$feature => $this->features[$feature] ?? false])
                ->all(),
        ];
    }
}
