<?php

namespace Tests\Feature\Concerns;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Role;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Spatie\Permission\PermissionRegistrar;

trait BuildsSalonFixtures
{
    protected function seedRoles(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (RolesPermissionsSeeder::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'Salon Owner', 'guard_name' => 'web'])
            ->givePermissionTo(RolesPermissionsSeeder::PERMISSIONS);

        Role::firstOrCreate(['name' => 'Beautician', 'guard_name' => 'web'])
            ->syncPermissions(['appointments.view', 'commissions.view_own', 'payslips.view_own']);
    }

    /**
     * @return array{0: Tenant, 1: User, 2: Branch}
     */
    protected function makeSalon(array $features = []): array
    {
        $tenant = Tenant::create([
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
            'email' => fake()->unique()->safeEmail(),
            'status' => 'active',
            'timezone' => 'Asia/Colombo',
            'currency' => 'LKR',
        ]);

        $plan = Plan::create([
            'name' => fake()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'price' => 0,
            'billing_period' => 'trial',
            'max_branches' => 10,
            'max_staff' => 10,
            'max_users' => 10,
            'max_customers' => null,
            'features' => array_fill_keys(array_merge([
                'appointment_calendar',
                'customer_management',
                'services',
                'pos_billing',
                'inventory',
                'staff_commissions',
            ], $features), true),
            'is_active' => true,
        ]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'trialing',
            'price' => 0,
            'billing_period' => 'trial',
            'entitlements' => $plan->entitlementSnapshot(),
            'starts_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);

        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'email_verified_at' => now()]);
        $owner->assignRole('Salon Owner');

        $branch = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Colombo ' . fake()->unique()->numberBetween(100, 999),
            'code' => 'CMB-' . fake()->unique()->numberBetween(1000, 9999),
            'status' => Branch::STATUS_ACTIVE,
            'is_active' => true,
            'is_main' => true,
            'country' => 'LK',
            'currency' => 'LKR',
            'timezone' => 'Asia/Colombo',
        ]);

        return [$tenant, $owner, $branch];
    }

    protected function makeProduct(Tenant $tenant, array $attributes = []): Product
    {
        $name = $attributes['name'] ?? 'Product ' . fake()->unique()->numberBetween(1000, 99999);

        return Product::withoutTenantScope()->create(array_merge([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'sku' => 'SKU-' . fake()->unique()->numberBetween(10000, 99999),
            'product_type' => Product::TYPE_BOTH,
            'cost_price' => 100,
            'selling_price' => 250,
            'track_inventory' => true,
            'allow_negative_stock' => false,
            'is_sellable' => true,
            'is_consumable' => true,
            'is_active' => true,
            'reorder_level' => 0,
        ], $attributes));
    }

    protected function makeService(Tenant $tenant, Branch $branch, array $attributes = []): Service
    {
        $name = $attributes['name'] ?? 'Service ' . fake()->unique()->numberBetween(1000, 99999);

        $service = Service::withoutTenantScope()->create(array_merge([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'price' => 3000,
            'duration_minutes' => 60,
            'default_price' => 3000,
            'default_duration_minutes' => 60,
            'is_active' => true,
        ], $attributes));

        $service->branches()->attach($branch->id, [
            'tenant_id' => $tenant->id,
            'price' => null,
            'duration_minutes' => null,
            'is_active' => true,
        ]);

        return $service;
    }

    protected function makeStaff(Tenant $tenant, Branch $branch, ?User $user = null): Staff
    {
        $staff = Staff::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user?->id,
            'employee_code' => 'STF-' . fake()->unique()->numberBetween(1000, 99999),
            'first_name' => 'Nadeesha',
            'last_name' => 'Perera',
            'status' => Staff::STATUS_ACTIVE,
            'is_bookable' => true,
        ]);

        $staff->branches()->attach($branch->id, [
            'tenant_id' => $tenant->id,
            'is_primary' => true,
            'status' => 'active',
        ]);

        return $staff;
    }

    protected function enableSms($tenant, $user): void
    {
        app(\App\Services\Settings\SettingService::class)->setMany($tenant, null, [
            'notification.sms_enabled' => true,
            'notification.channel' => 'sms',
            'integration.sms_provider' => 'notify_lk',
            'integration.sms_user_id' => '12345',
            'integration.sms_api_key' => 'secret-key',
            'integration.sms_sender_id' => 'GlamSalon',
        ], $user);
    }

    protected function makeCustomer(Tenant $tenant, Branch $branch): Customer
    {
        return Customer::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'customer_code' => 'CUS-' . fake()->unique()->numberBetween(10000, 99999),
            'first_name' => 'Emma',
            'last_name' => 'Silva',
            'phone' => '0771234567',
            'status' => Customer::STATUS_ACTIVE,
        ]);
    }
}
