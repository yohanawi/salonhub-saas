<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (RolesPermissionsSeeder::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'Salon Owner', 'guard_name' => 'web'])
            ->givePermissionTo(RolesPermissionsSeeder::PERMISSIONS);

        Role::firstOrCreate(['name' => 'Receptionist', 'guard_name' => 'web'])
            ->givePermissionTo(['customer.view', 'customer.create', 'customer.update', 'customer.view_notes']);

        Role::firstOrCreate(['name' => 'Beautician', 'guard_name' => 'web'])
            ->givePermissionTo(['customer.view']);

        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
    }

    public function test_owner_can_create_customer_with_primary_branch_and_note(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner(maxCustomers: 10);
        $branch = $this->branch($tenant, 'Colombo', 'CMB');

        $this->actingAs($owner)
            ->post(route('customer-management.customers.store'), $this->customerPayload($branch))
            ->assertRedirect();

        $customer = Customer::where('tenant_id', $tenant->id)->where('phone', '0771234567')->firstOrFail();

        $this->assertSame($branch->id, $customer->branch_id);
        $this->assertSame('CUS-' . str_pad((string) $customer->id, 6, '0', STR_PAD_LEFT), $customer->customer_code);
        $this->assertTrue($customer->marketing_consent);
        $this->assertDatabaseHas('customer_notes', [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'note' => 'Prefers senior stylist.',
        ]);
    }

    public function test_customer_search_and_profile_are_available(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner(maxCustomers: 10);
        $branch = $this->branch($tenant, 'Colombo', 'CMB');
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'customer_code' => 'CUS-000123',
            'first_name' => 'Kasun',
            'last_name' => 'Silva',
            'phone' => '0771234567',
            'email' => 'kasun@example.test',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $this->actingAs($owner)
            ->get(route('customer-management.customers.index', ['search' => '0771234567']))
            ->assertOk()
            ->assertSee('Kasun Silva')
            ->assertSee('CUS-000123');

        $this->actingAs($owner)
            ->get(route('customer-management.customers.show', $customer))
            ->assertOk()
            ->assertSee('Customer Profile')
            ->assertSee('No appointment history available yet.');
    }

    public function test_tenant_cannot_assign_customer_to_other_tenant_branch(): void
    {
        [, $owner] = $this->tenantWithOwner(maxCustomers: 10);
        [$otherTenant] = $this->tenantWithOwner(maxCustomers: 10);
        $otherBranch = $this->branch($otherTenant, 'Other Branch', 'OTH');

        $this->actingAs($owner)
            ->post(route('customer-management.customers.store'), $this->customerPayload($otherBranch))
            ->assertSessionHasErrors('branch_id');

        $this->assertDatabaseMissing('customers', [
            'phone' => '0771234567',
        ]);
    }

    public function test_subscription_customer_limit_is_enforced(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner(maxCustomers: 1);
        $branch = $this->branch($tenant, 'Colombo', 'CMB');

        Customer::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'customer_code' => 'CUS-EXISTING',
            'first_name' => 'Existing',
            'phone' => '0700000000',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $this->actingAs($owner)
            ->post(route('customer-management.customers.store'), $this->customerPayload($branch))
            ->assertSessionHasErrors('customer');
    }

    public function test_read_only_role_cannot_create_customer(): void
    {
        [$tenant] = $this->tenantWithOwner(maxCustomers: 10);
        $branch = $this->branch($tenant, 'Colombo', 'CMB');
        $beautician = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);
        $beautician->assignRole('Beautician');

        $this->actingAs($beautician)
            ->post(route('customer-management.customers.store'), $this->customerPayload($branch))
            ->assertForbidden();
    }

    public function test_super_admin_can_create_customer_for_selected_tenant(): void
    {
        [$tenant] = $this->tenantWithOwner(maxCustomers: 10);
        $branch = $this->branch($tenant, 'Colombo', 'CMB');
        $superAdmin = User::factory()->create([
            'tenant_id' => null,
            'email_verified_at' => now(),
        ]);
        $superAdmin->assignRole('Super Admin');

        $this->actingAs($superAdmin)
            ->get(route('customer-management.customers.index'))
            ->assertOk()
            ->assertSee('Customer Directory');

        $this->actingAs($superAdmin)
            ->post(route('customer-management.customers.store'), array_merge($this->customerPayload($branch), [
                'tenant_id' => $tenant->id,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'tenant_id' => $tenant->id,
            'phone' => '0771234567',
        ]);
    }

    private function tenantWithOwner(int $maxCustomers): array
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
            'max_customers' => $maxCustomers,
            'features' => ['customer_management' => true],
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

        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);
        $owner->assignRole('Salon Owner');

        return [$tenant, $owner];
    }

    private function branch(Tenant $tenant, string $name, string $code): Branch
    {
        return Branch::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'code' => $code,
            'status' => Branch::STATUS_ACTIVE,
            'is_active' => true,
            'is_main' => false,
            'country' => 'LK',
            'currency' => 'LKR',
            'timezone' => 'Asia/Colombo',
        ]);
    }

    private function customerPayload(Branch $branch): array
    {
        return [
            'branch_id' => $branch->id,
            'first_name' => 'Kasun',
            'last_name' => 'Silva',
            'phone' => '0771234567',
            'email' => 'kasun@example.test',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'address' => 'No. 10, Main Street, Colombo',
            'notes' => 'Prefers WhatsApp reminders.',
            'note' => 'Prefers senior stylist.',
            'marketing_consent' => 1,
            'status' => Customer::STATUS_ACTIVE,
        ];
    }
}
