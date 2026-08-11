<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free Trial',
                'slug' => 'free-trial',
                'price' => 0,
                'billing_period' => 'trial',
                'max_branches' => 1,
                'max_staff' => 5,
                'max_users' => 3,
                'max_customers' => 10000,
                'trial_days' => 14,
                'is_recommended' => false,
                'sort_order' => 10,
                'features' => [
                    'customer_management' => true,
                    'services' => true,
                    'appointment_calendar' => true,
                    'appointments_unlimited' => true,
                    'pos_billing' => true,
                    'basic_reports' => true,
                    'inventory' => true,
                    'expenses' => true,
                    'staff_commissions' => true,
                    'advanced_reports' => true,
                    'multi_branch_reports' => true,
                    'audit_logs' => 'limited',
                    'priority_support' => false,
                    'no_credit_card_required' => true,
                ],
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'price' => 2990,
                'billing_period' => 'monthly',
                'max_branches' => 1,
                'max_staff' => 5,
                'max_users' => 3,
                'max_customers' => 1000,
                'trial_days' => 0,
                'is_recommended' => false,
                'sort_order' => 20,
                'features' => [
                    'customer_management' => true,
                    'services' => true,
                    'appointment_calendar' => true,
                    'appointments_unlimited' => true,
                    'pos_billing' => true,
                    'basic_reports' => true,
                    'inventory' => false,
                    'expenses' => true,
                    'staff_commissions' => false,
                    'advanced_reports' => false,
                    'multi_branch_reports' => false,
                    'audit_logs' => false,
                    'priority_support' => false,
                ],
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'price' => 6990,
                'billing_period' => 'monthly',
                'max_branches' => 3,
                'max_staff' => 20,
                'max_users' => 10,
                'max_customers' => 10000,
                'trial_days' => 0,
                'is_recommended' => true,
                'sort_order' => 30,
                'features' => [
                    'customer_management' => true,
                    'services' => true,
                    'appointment_calendar' => true,
                    'appointments_unlimited' => true,
                    'pos_billing' => true,
                    'basic_reports' => true,
                    'inventory' => true,
                    'expenses' => true,
                    'staff_commissions' => true,
                    'advanced_reports' => true,
                    'multi_branch_reports' => true,
                    'audit_logs' => 'limited',
                    'priority_support' => false,
                ],
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'price' => 14990,
                'billing_period' => 'monthly',
                'max_branches' => 10,
                'max_staff' => 75,
                'max_users' => 30,
                'max_customers' => null,
                'trial_days' => 0,
                'is_recommended' => false,
                'sort_order' => 40,
                'features' => [
                    'customer_management' => true,
                    'services' => true,
                    'appointment_calendar' => true,
                    'appointments_unlimited' => true,
                    'pos_billing' => true,
                    'basic_reports' => true,
                    'inventory' => true,
                    'expenses' => true,
                    'staff_commissions' => true,
                    'advanced_reports' => true,
                    'multi_branch_reports' => true,
                    'audit_logs' => true,
                    'priority_support' => true,
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan + ['is_active' => true]
            );
        }
    }
}
