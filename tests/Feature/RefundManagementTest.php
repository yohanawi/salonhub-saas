<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\LoyaltyPointTransaction;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Role;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffCommission;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Settings\SettingService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RefundManagementTest extends TestCase
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

        $this->salonSetup();
    }

    public function test_full_product_return_restocks_and_marks_invoice_refunded(): void
    {
        $invoice = $this->sellProducts(2);
        $item = $invoice->items->firstWhere('item_type', 'product');
        $this->assertSame(3, $this->stockOnHand());

        $this->actingAs($this->owner)
            ->get(route('billing.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Refund / Return')
            ->assertSee('Refund Now');

        $this->actingAs($this->owner)
            ->post(route('billing.invoices.refunds.store', $invoice), [
                'payment_method_id' => $this->cash->id,
                'amount' => 1500,
                'reason' => 'Allergic reaction',
                'items' => [$item->id => ['quantity' => 2, 'restock' => 1]],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('billing.invoices.show', $invoice));

        $invoice->refresh();
        $refund = Refund::withoutTenantScope()->where('sale_id', $invoice->id)->firstOrFail();

        $this->assertSame(Refund::STATUS_COMPLETED, $refund->status);
        $this->assertStringStartsWith('REF-', $refund->refund_number);
        $this->assertSame($this->owner->id, $refund->processed_by);
        $this->assertSame(Invoice::PAYMENT_REFUNDED, $invoice->payment_status);
        $this->assertEquals('1500.00', $invoice->refunded_amount);
        $this->assertSame(5, $this->stockOnHand());
        $this->assertDatabaseHas('refund_items', ['refund_id' => $refund->id, 'sale_item_id' => $item->id, 'quantity' => 2, 'restock' => true]);

        $this->actingAs($this->owner)
            ->get(route('billing.invoices.show', $invoice))
            ->assertOk()
            ->assertSee($refund->refund_number)
            ->assertDontSee('Refund Now');
    }

    public function test_returned_product_can_be_written_off_instead_of_restocked(): void
    {
        $invoice = $this->sellProducts(2);
        $item = $invoice->items->firstWhere('item_type', 'product');

        $this->refund($invoice, ['amount' => 750, 'items' => [$item->id => ['quantity' => 1, 'restock' => 0]]])
            ->assertSessionHasNoErrors();

        $this->assertSame(3, $this->stockOnHand());
        $this->assertSame(Invoice::PAYMENT_PARTIALLY_REFUNDED, $invoice->fresh()->payment_status);
    }

    public function test_partial_service_refund_reverses_matching_commission_and_loyalty_points(): void
    {
        $loyalty = app(LoyaltyService::class);
        $loyalty->ensureDefaultRule($loyalty->ensureDefaultProgram($this->tenant));
        $customer = $this->customer();

        $invoice = $this->sell([
            'customer_mode' => 'existing',
            'customer_id' => $customer->id,
            'service_items' => [['service_id' => $this->service->id, 'staff_id' => $this->stylist->id, 'quantity' => 2]],
            'amount' => 6000,
        ]);
        $item = $invoice->items->firstWhere('item_type', 'service');

        $earned = LoyaltyPointTransaction::withoutTenantScope()->where('source_type', Invoice::class)->where('source_id', $invoice->id)->where('type', LoyaltyPointTransaction::TYPE_EARN)->firstOrFail();
        $this->assertSame(60, (int) $earned->points);
        $this->assertEquals(600.0, (float) StaffCommission::withoutTenantScope()->where('invoice_id', $invoice->id)->sum('commission_amount'));

        $this->refund($invoice, ['amount' => 3000, 'items' => [$item->id => ['quantity' => 1]]])
            ->assertSessionHasNoErrors();

        $refund = Refund::withoutTenantScope()->where('sale_id', $invoice->id)->firstOrFail();
        $clawback = StaffCommission::withoutTenantScope()->where('source_type', Refund::class)->where('source_id', $refund->id)->firstOrFail();

        $this->assertEquals(-300.0, (float) $clawback->commission_amount);
        $this->assertSame($this->stylist->id, $clawback->staff_id);
        $this->assertEquals(300.0, (float) StaffCommission::withoutTenantScope()->where('invoice_id', $invoice->id)->sum('commission_amount'));

        $reversal = LoyaltyPointTransaction::withoutTenantScope()->where('source_type', Refund::class)->where('source_id', $refund->id)->firstOrFail();
        $this->assertSame(-30, (int) $reversal->points);
        $this->assertSame(30, (int) $loyalty->accountFor($customer)->available_points);
        $this->assertSame(Invoice::PAYMENT_PARTIALLY_REFUNDED, $invoice->fresh()->payment_status);
    }

    public function test_money_only_refund_leaves_stock_and_items_alone(): void
    {
        $invoice = $this->sellProducts(2);

        $this->refund($invoice, ['amount' => 200, 'reason' => 'Goodwill for late service'])
            ->assertSessionHasNoErrors();

        $refund = Refund::withoutTenantScope()->where('sale_id', $invoice->id)->firstOrFail();

        $this->assertCount(0, $refund->items);
        $this->assertSame(3, $this->stockOnHand());
        $this->assertEquals('200.00', $invoice->fresh()->refunded_amount);
    }

    public function test_refunds_cannot_exceed_the_amount_paid(): void
    {
        $invoice = $this->sellProducts(2);

        $this->refund($invoice, ['amount' => 1600])->assertSessionHasErrors('amount');

        $this->refund($invoice, ['amount' => 1000])->assertSessionHasNoErrors();
        $this->refund($invoice, ['amount' => 600])->assertSessionHasErrors('amount');
        $this->refund($invoice, ['amount' => 500])->assertSessionHasNoErrors();

        $invoice->refresh();
        $this->assertEquals('1500.00', $invoice->refunded_amount);
        $this->assertSame(Invoice::PAYMENT_REFUNDED, $invoice->payment_status);

        $this->refund($invoice, ['amount' => 1])->assertSessionHasErrors('refund');
    }

    public function test_cannot_return_more_units_than_were_sold(): void
    {
        $invoice = $this->sellProducts(2);
        $item = $invoice->items->firstWhere('item_type', 'product');

        $this->refund($invoice, ['amount' => 750, 'items' => [$item->id => ['quantity' => 1, 'restock' => 1]]])->assertSessionHasNoErrors();
        $this->refund($invoice, ['amount' => 750, 'items' => [$item->id => ['quantity' => 2, 'restock' => 1]]])->assertSessionHasErrors("items.{$item->id}.quantity");

        $this->assertSame(4, $this->stockOnHand());
    }

    public function test_refund_request_waits_for_approval_and_can_be_approved_or_rejected(): void
    {
        $invoice = $this->sellProducts(2);
        $item = $invoice->items->firstWhere('item_type', 'product');
        $requester = $this->userWith(['billing.view', 'refunds.create']);

        $this->actingAs($requester)
            ->post(route('billing.invoices.refunds.store', $invoice), [
                'payment_method_id' => $this->cash->id,
                'amount' => 750,
                'reason' => 'Damaged bottle',
                'items' => [$item->id => ['quantity' => 1, 'restock' => 1]],
            ])
            ->assertSessionHasNoErrors();

        $pending = Refund::withoutTenantScope()->where('sale_id', $invoice->id)->firstOrFail();

        $this->assertSame(Refund::STATUS_PENDING, $pending->status);
        $this->assertSame($requester->id, $pending->requested_by);
        $this->assertEquals('0.00', $invoice->fresh()->refunded_amount);
        $this->assertSame(3, $this->stockOnHand());

        $this->actingAs($requester)
            ->post(route('billing.invoices.refunds.approve', [$invoice, $pending]))
            ->assertForbidden();

        // The pending request holds its amount, so a second request cannot claim the same money.
        $this->refund($invoice, ['amount' => 800])->assertSessionHasErrors('amount');

        $this->actingAs($this->owner)
            ->post(route('billing.invoices.refunds.approve', [$invoice, $pending]))
            ->assertSessionHasNoErrors();

        $this->assertSame(Refund::STATUS_COMPLETED, $pending->fresh()->status);
        $this->assertEquals('750.00', $invoice->fresh()->refunded_amount);
        $this->assertSame(4, $this->stockOnHand());

        $this->actingAs($requester)
            ->post(route('billing.invoices.refunds.store', $invoice), [
                'payment_method_id' => $this->cash->id,
                'amount' => 100,
                'reason' => 'Second request',
            ])
            ->assertSessionHasNoErrors();

        $second = Refund::withoutTenantScope()->where('sale_id', $invoice->id)->where('status', Refund::STATUS_PENDING)->firstOrFail();

        $this->actingAs($this->owner)
            ->post(route('billing.invoices.refunds.reject', [$invoice, $second]), ['rejection_reason' => 'Not eligible'])
            ->assertSessionHasNoErrors();

        $this->assertSame(Refund::STATUS_REJECTED, $second->fresh()->status);
        $this->assertEquals('750.00', $invoice->fresh()->refunded_amount);
    }

    public function test_user_without_refund_permission_cannot_refund(): void
    {
        $invoice = $this->sellProducts(1);
        $cashier = $this->userWith(['billing.view', 'billing.checkout']);

        $this->actingAs($cashier)
            ->post(route('billing.invoices.refunds.store', $invoice), [
                'payment_method_id' => $this->cash->id,
                'amount' => 750,
                'reason' => 'No permission',
            ])
            ->assertForbidden();

        $this->actingAs($cashier)
            ->get(route('billing.invoices.show', $invoice))
            ->assertOk()
            ->assertDontSee('Refund / Return');
    }

    public function test_refunded_invoice_cannot_be_voided(): void
    {
        $invoice = $this->sellProducts(2);
        $item = $invoice->items->firstWhere('item_type', 'product');

        $this->refund($invoice, ['amount' => 750, 'items' => [$item->id => ['quantity' => 1, 'restock' => 1]]])->assertSessionHasNoErrors();

        $this->actingAs($this->owner)
            ->post(route('billing.invoices.void', $invoice), ['void_reason' => 'Trying to void'])
            ->assertSessionHasErrors('invoice');

        $this->assertSame(Invoice::STATUS_ISSUED, $invoice->fresh()->status);
        $this->assertSame(4, $this->stockOnHand());
    }

    public function test_refunds_respect_the_allow_refunds_setting(): void
    {
        $invoice = $this->sellProducts(1);
        app(SettingService::class)->setMany($this->tenant, null, ['pos.allow_refunds' => false], $this->owner);

        $this->refund($invoice, ['amount' => 100])->assertSessionHasErrors('refund');
    }

    public function test_unpaid_balance_invoice_cannot_be_refunded(): void
    {
        $invoice = $this->sell([
            'service_items' => [],
            'product_items' => [['product_id' => $this->product->id, 'quantity' => 2]],
            'amount' => 500,
        ]);

        $this->assertSame(Invoice::PAYMENT_PARTIAL, $invoice->payment_status);
        $this->refund($invoice, ['amount' => 100])->assertSessionHasErrors('refund');
    }

    public function test_dashboard_revenue_is_net_of_refunds(): void
    {
        $invoice = $this->sellProducts(2);
        $this->refund($invoice, ['amount' => 500])->assertSessionHasNoErrors();

        $this->actingAs($this->owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('LKR 1,000.00');
    }

    private function refund(Invoice $invoice, array $data)
    {
        return $this->actingAs($this->owner)->post(route('billing.invoices.refunds.store', $invoice), array_merge([
            'payment_method_id' => $this->cash->id,
            'reason' => 'Customer request',
        ], $data));
    }

    private function sellProducts(int $quantity): Invoice
    {
        return $this->sell([
            'service_items' => [],
            'product_items' => [['product_id' => $this->product->id, 'quantity' => $quantity]],
            'amount' => 750 * $quantity,
        ]);
    }

    private function sell(array $payload): Invoice
    {
        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), array_merge([
                'customer_mode' => 'walk_in',
                'service_items' => [],
                'product_items' => [],
                'payment_method_id' => $this->cash->id,
            ], $payload))
            ->assertSessionHasNoErrors();

        return Invoice::withoutTenantScope()->with('items')->where('tenant_id', $this->tenant->id)->latest('id')->firstOrFail();
    }

    private function userWith(array $permissions): User
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id, 'email_verified_at' => now()]);
        $user->givePermissionTo($permissions);
        $user->branches()->attach($this->branch->id, ['tenant_id' => $this->tenant->id]);

        return $user;
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
                'staff_commissions' => true,
                'loyalty_program' => true,
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

        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'email_verified_at' => now()]);
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
            'price' => 3000,
            'duration_minutes' => 45,
            'default_price' => 3000,
            'default_duration_minutes' => 45,
            'is_active' => true,
        ]);
        $this->service->branches()->attach($this->branch->id, [
            'tenant_id' => $this->tenant->id,
            'price' => 3000,
            'duration_minutes' => 45,
            'is_active' => true,
        ]);

        $this->stylist = Staff::create([
            'tenant_id' => $this->tenant->id,
            'employee_code' => 'STF-0001',
            'first_name' => 'Nadeesha',
            'last_name' => 'Perera',
            'job_title' => 'Stylist',
            'status' => Staff::STATUS_ACTIVE,
            'is_bookable' => true,
            'commission_type' => 'percentage',
            'commission_value' => 10,
        ]);
        $this->stylist->branches()->attach($this->branch->id, ['tenant_id' => $this->tenant->id, 'is_primary' => true, 'status' => 'active']);
        $this->stylist->services()->attach($this->service->id, ['tenant_id' => $this->tenant->id, 'status' => 'active']);

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
