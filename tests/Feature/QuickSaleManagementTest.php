<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Role;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffCommission;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Settings\SettingService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class QuickSaleManagementTest extends TestCase
{
    use DatabaseTransactions;

    private Tenant $tenant;
    private User $owner;
    private Branch $branch;
    private Service $service;
    private Staff $stylist;
    private Product $product;
    private PaymentMethod $cash;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (RolesPermissionsSeeder::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'Salon Owner', 'guard_name' => 'web'])
            ->givePermissionTo(RolesPermissionsSeeder::PERMISSIONS);

        Role::firstOrCreate(['name' => 'Beautician', 'guard_name' => 'web'])
            ->givePermissionTo(['appointments.view']);

        $this->salonSetup();
    }

    public function test_quick_sale_page_lists_branch_services_staff_and_products(): void
    {
        $this->actingAs($this->owner)
            ->get(route('billing.quick-sale.create'))
            ->assertOk()
            ->assertSee('Quick Sale')
            ->assertSee('Hair Cut')
            ->assertSee('Nadeesha Perera')
            ->assertSee('Argan Shampoo')
            ->assertSee('Walk-in (No Customer)');

        $this->actingAs($this->owner)
            ->get(route('billing.invoices.index'))
            ->assertOk()
            ->assertSee(route('billing.quick-sale.create'), false);
    }

    public function test_walk_in_can_buy_products_without_an_appointment_or_customer(): void
    {
        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'customer_mode' => 'walk_in',
                'service_items' => [],
                'product_items' => [['product_id' => $this->product->id, 'quantity' => 2]],
                'amount' => 1500,
                'cash_received' => 2000,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $invoice = Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->firstOrFail();

        $this->assertNull($invoice->appointment_id);
        $this->assertNull($invoice->customer_id);
        $this->assertSame($this->branch->id, $invoice->branch_id);
        $this->assertStringStartsWith('INV-', $invoice->invoice_number);
        $this->assertEquals('1500.00', $invoice->total);
        $this->assertSame(Invoice::PAYMENT_PAID, $invoice->payment_status);
        $this->assertDatabaseHas('payments', ['sale_id' => $invoice->id, 'amount' => 1500, 'change_given' => 500]);
        $this->assertSame(3, $this->stockOnHand());
    }

    public function test_service_sale_uses_staff_price_and_pays_commission_on_service_and_product(): void
    {
        $customer = $this->customer();

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'customer_mode' => 'existing',
                'customer_id' => $customer->id,
                'service_items' => [['service_id' => $this->service->id, 'staff_id' => $this->stylist->id, 'quantity' => 1]],
                'product_items' => [['product_id' => $this->product->id, 'quantity' => 2, 'staff_id' => $this->stylist->id]],
                'amount' => 4500,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $invoice = Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->firstOrFail();

        // Staff custom price 3000 overrides the 2500 branch price.
        $this->assertSame($customer->id, $invoice->customer_id);
        $this->assertEquals('4500.00', $invoice->total);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $invoice->id,
            'item_type' => 'service',
            'service_id' => $this->service->id,
            'staff_id' => $this->stylist->id,
            'unit_price' => 3000,
        ]);

        $commissions = StaffCommission::withoutTenantScope()->where('invoice_id', $invoice->id)->where('staff_id', $this->stylist->id)->get();

        $this->assertCount(2, $commissions);
        $this->assertEqualsCanonicalizing([300.0, 150.0], $commissions->map(fn ($commission) => (float) $commission->commission_amount)->all());
    }

    public function test_service_quantity_is_charged_per_unit(): void
    {
        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'service_items' => [['service_id' => $this->service->id, 'staff_id' => $this->stylist->id, 'quantity' => 2]],
                'amount' => 6000,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertEquals('6000.00', Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->value('total'));
    }

    public function test_new_customer_is_created_with_the_sale(): void
    {
        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'customer_mode' => 'new',
                'new_customer' => ['first_name' => 'Kasuni', 'last_name' => 'Fernando', 'phone' => '0779876543'],
                'amount' => 3000,
            ]))
            ->assertSessionHasNoErrors();

        $customer = Customer::withoutTenantScope()->where('tenant_id', $this->tenant->id)->where('phone', '0779876543')->firstOrFail();

        $this->assertSame($this->branch->id, $customer->branch_id);
        $this->assertSame($customer->id, Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->value('customer_id'));
    }

    public function test_new_customer_with_a_phone_on_file_reuses_that_customer(): void
    {
        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'customer_mode' => 'new',
                'new_customer' => ['first_name' => 'Kasuni', 'phone' => '0779876543'],
                'amount' => 3000,
            ]))
            ->assertSessionHasNoErrors();

        // The same person typed in again, with the phone written differently.
        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'customer_mode' => 'new',
                'new_customer' => ['first_name' => 'Kasuni', 'phone' => '+94 77 987 6543'],
                'amount' => 3000,
            ]))
            ->assertSessionHasNoErrors();

        $customers = Customer::withoutTenantScope()->where('tenant_id', $this->tenant->id)->where('first_name', 'Kasuni')->pluck('id');
        $this->assertCount(1, $customers);
        $this->assertSame(2, Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->where('customer_id', $customers->first())->count());
    }

    public function test_failed_sale_does_not_leave_a_new_customer_behind(): void
    {
        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'customer_mode' => 'new',
                'new_customer' => ['first_name' => 'Kasuni', 'phone' => '0779876543'],
                'service_items' => [],
                'product_items' => [['product_id' => $this->product->id, 'quantity' => 9]],
                'amount' => 6750,
            ]))
            ->assertSessionHasErrors('product_items.0.quantity');

        $this->assertFalse(Customer::withoutTenantScope()->where('tenant_id', $this->tenant->id)->where('phone', '0779876543')->exists());
        $this->assertFalse(Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->exists());
        $this->assertSame(5, $this->stockOnHand());
    }

    public function test_walk_in_without_customer_is_rejected_when_disabled(): void
    {
        app(SettingService::class)->setMany($this->tenant, null, ['booking.allow_walk_ins' => false], $this->owner);

        $this->actingAs($this->owner)
            ->get(route('billing.quick-sale.create'))
            ->assertOk()
            ->assertDontSee('Walk-in (No Customer)');

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload(['customer_mode' => 'walk_in', 'amount' => 3000]))
            ->assertSessionHasErrors('customer_mode');

        $this->assertFalse(Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->exists());
    }

    public function test_service_requires_a_staff_member_assigned_to_it(): void
    {
        $unassigned = $this->staffAtBranch('STF-0002', 'Ruwan');

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'service_items' => [['service_id' => $this->service->id, 'staff_id' => $unassigned->id, 'quantity' => 1]],
            ]))
            ->assertSessionHasErrors('service_items.0.staff_id');

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'service_items' => [['service_id' => $this->service->id, 'staff_id' => null, 'quantity' => 1]],
            ]))
            ->assertSessionHasErrors('service_items.0.staff_id');

        $this->assertFalse(Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->exists());
    }

    public function test_service_not_offered_at_branch_is_rejected(): void
    {
        $this->service->branches()->updateExistingPivot($this->branch->id, ['is_active' => false]);

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload())
            ->assertSessionHasErrors('service_items.0.service_id');
    }

    public function test_empty_sale_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload(['service_items' => [], 'product_items' => []]))
            ->assertSessionHasErrors('items');
    }

    public function test_cross_tenant_customer_is_rejected(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Other Salon',
            'slug' => fake()->unique()->slug(),
            'email' => fake()->unique()->safeEmail(),
            'status' => 'active',
        ]);
        $foreignCustomer = Customer::create([
            'tenant_id' => $otherTenant->id,
            'customer_code' => 'CUS-999999',
            'first_name' => 'Other',
            'phone' => '0710000000',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload(['customer_mode' => 'existing', 'customer_id' => $foreignCustomer->id]))
            ->assertSessionHasErrors('customer_id');
    }

    public function test_staff_without_billing_permission_cannot_use_quick_sale(): void
    {
        $beautician = User::factory()->create(['tenant_id' => $this->tenant->id, 'email_verified_at' => now()]);
        $beautician->assignRole('Beautician');

        $this->actingAs($beautician)->get(route('billing.quick-sale.create'))->assertForbidden();
        $this->actingAs($beautician)->post(route('billing.quick-sale.store'), $this->payload())->assertForbidden();
    }

    public function test_cashier_with_manual_discount_permission_can_discount(): void
    {
        $cashier = User::factory()->create(['tenant_id' => $this->tenant->id, 'email_verified_at' => now()]);
        $cashier->assignRole(Role::firstOrCreate(['name' => 'Cashier', 'guard_name' => 'web'])
            ->givePermissionTo(['billing.view', 'billing.checkout', 'payments.create', 'discount.manual']));
        $cashier->branches()->attach($this->branch->id, ['tenant_id' => $this->tenant->id]);

        $this->actingAs($cashier)
            ->get(route('billing.quick-sale.create'))
            ->assertOk()
            ->assertSee('name="discount_amount"', false);

        $this->actingAs($cashier)
            ->post(route('billing.quick-sale.store'), $this->payload(['discount_amount' => 500, 'amount' => 2500]))
            ->assertSessionHasNoErrors();

        $this->assertEquals('2500.00', Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->value('total'));
    }

    public function test_voiding_a_quick_sale_returns_stock(): void
    {
        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'service_items' => [],
                'product_items' => [['product_id' => $this->product->id, 'quantity' => 2]],
                'amount' => 1500,
            ]))
            ->assertSessionHasNoErrors();

        $invoice = Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->firstOrFail();
        $this->assertSame(3, $this->stockOnHand());

        $this->actingAs($this->owner)
            ->post(route('billing.invoices.void', $invoice), ['void_reason' => 'Customer returned item'])
            ->assertRedirect();

        $this->assertSame(Invoice::STATUS_VOID, $invoice->fresh()->status);
        $this->assertSame(5, $this->stockOnHand());
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'customer_mode' => 'walk_in',
            'service_items' => [['service_id' => $this->service->id, 'staff_id' => $this->stylist->id, 'quantity' => 1]],
            'product_items' => [],
            'payment_method_id' => $this->cash->id,
            'amount' => 3000,
        ], $overrides);
    }

    private function stockOnHand(): int
    {
        return (int) Inventory::withoutTenantScope()
            ->where('branch_id', $this->branch->id)
            ->where('product_id', $this->product->id)
            ->value('quantity_on_hand');
    }

    private function customer(): Customer
    {
        return Customer::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUS-000001',
            'first_name' => 'Emma',
            'last_name' => 'Silva',
            'phone' => '0771234567',
            'status' => Customer::STATUS_ACTIVE,
        ]);
    }

    private function staffAtBranch(string $code, string $firstName): Staff
    {
        $staff = Staff::create([
            'tenant_id' => $this->tenant->id,
            'employee_code' => $code,
            'first_name' => $firstName,
            'last_name' => 'Perera',
            'job_title' => 'Stylist',
            'status' => Staff::STATUS_ACTIVE,
            'is_bookable' => true,
            'commission_type' => 'percentage',
            'commission_value' => 10,
        ]);

        $staff->branches()->attach($this->branch->id, [
            'tenant_id' => $this->tenant->id,
            'is_primary' => true,
            'status' => 'active',
        ]);

        return $staff;
    }

    private function salonSetup(): void
    {
        $this->tenant = Tenant::create([
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
            'features' => [
                'appointment_calendar' => true,
                'customer_management' => true,
                'services' => true,
                'pos_billing' => true,
                'inventory' => true,
                'staff_commissions' => true,
            ],
            'is_active' => true,
        ]);

        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $plan->id,
            'status' => 'trialing',
            'price' => 0,
            'billing_period' => 'trial',
            'entitlements' => $plan->entitlementSnapshot(),
            'starts_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);

        $this->owner = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email_verified_at' => now(),
        ]);
        $this->owner->assignRole('Salon Owner');

        $this->branch = Branch::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Colombo',
            'code' => 'CMB',
            'status' => Branch::STATUS_ACTIVE,
            'is_active' => true,
            'is_main' => true,
            'country' => 'LK',
            'currency' => 'LKR',
            'timezone' => 'Asia/Colombo',
        ]);

        $this->service = Service::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Hair Cut',
            'slug' => 'hair-cut',
            'price' => 2500,
            'duration_minutes' => 45,
            'default_price' => 2500,
            'default_duration_minutes' => 45,
            'is_active' => true,
        ]);

        $this->service->branches()->attach($this->branch->id, [
            'tenant_id' => $this->tenant->id,
            'price' => 2500,
            'duration_minutes' => 45,
            'is_active' => true,
        ]);

        $this->stylist = $this->staffAtBranch('STF-0001', 'Nadeesha');
        $this->stylist->services()->attach($this->service->id, [
            'tenant_id' => $this->tenant->id,
            'custom_duration_minutes' => 60,
            'custom_price' => 3000,
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Argan Shampoo',
            'slug' => 'argan-shampoo',
            'sku' => 'SHAM-1001',
            'product_type' => Product::TYPE_RETAIL,
            'cost_price' => 400,
            'selling_price' => 750,
            'track_inventory' => true,
            'allow_negative_stock' => false,
            'is_sellable' => true,
            'is_consumable' => false,
            'is_active' => true,
            'reorder_level' => 1,
        ]);

        Inventory::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
            'quantity_on_hand' => 5,
            'quantity_reserved' => 0,
            'average_cost' => 400,
        ]);

        $this->cash = PaymentMethod::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Cash',
            'code' => 'cash',
            'type' => PaymentMethod::TYPE_CASH,
            'is_active' => true,
        ]);
    }
}
