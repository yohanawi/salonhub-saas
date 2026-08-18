<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StockMovement;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InventoryProductManagementTest extends TestCase
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
            ->givePermissionTo(['inventory.view', 'product.view', 'stock.view', 'stock_movement.view']);
    }

    public function test_owner_can_create_product_with_opening_stock_and_movement(): void
    {
        [$tenant, $owner, $branch] = $this->tenantSetup();

        $category = ProductCategory::create([
            'tenant_id' => $tenant->id,
            'name' => 'Hair Care',
            'slug' => 'hair-care',
            'is_active' => true,
        ]);

        $unit = Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Bottle',
            'symbol' => 'btl',
            'type' => Unit::TYPE_PIECE,
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('inventory.products.store'), [
                'category_id' => $category->id,
                'unit_id' => $unit->id,
                'name' => 'Argan Hair Serum',
                'sku' => 'SERUM-001',
                'product_type' => Product::TYPE_BOTH,
                'cost_price' => 1500,
                'selling_price' => 2500,
                'track_inventory' => 1,
                'is_active' => 1,
                'reorder_level' => 10,
                'opening_branch_id' => $branch->id,
                'opening_quantity' => 20,
                'opening_cost' => 1500,
            ])
            ->assertRedirect();

        $product = Product::withoutTenantScope()->where('tenant_id', $tenant->id)->where('sku', 'SERUM-001')->firstOrFail();

        $this->assertSame(Product::TYPE_BOTH, $product->product_type);
        $this->assertTrue($product->is_sellable);
        $this->assertTrue($product->is_consumable);

        $this->assertDatabaseHas('inventories', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 20,
            'quantity' => 20,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'type' => StockMovement::TYPE_OPENING_STOCK,
            'quantity' => 20,
            'quantity_before' => 0,
            'quantity_after' => 20,
            'created_by' => $owner->id,
        ]);
    }

    public function test_stock_adjustment_updates_stock_and_writes_ledger(): void
    {
        [$tenant, $owner, $branch] = $this->tenantSetup();
        $product = $this->productWithStock($tenant, $branch, 20);

        $this->actingAs($owner)
            ->post(route('inventory.adjustments.store'), [
                'branch_id' => $branch->id,
                'reason' => 'damaged',
                'notes' => 'Two bottles damaged',
                'items' => [
                    ['product_id' => $product->id, 'actual_quantity' => 18],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('inventories', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 18,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'type' => StockMovement::TYPE_ADJUSTMENT_OUT,
            'quantity' => -2,
            'quantity_before' => 20,
            'quantity_after' => 18,
        ]);
    }

    public function test_checkout_deducts_product_stock_and_void_returns_it(): void
    {
        [$tenant, $owner, $branch] = $this->tenantSetup();
        $appointment = $this->completedAppointment($tenant, $owner, $branch);
        $product = $this->productWithStock($tenant, $branch, 5, 400, 750);
        $cash = PaymentMethod::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cash',
            'code' => 'cash',
            'type' => PaymentMethod::TYPE_CASH,
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('billing.checkout.appointments.store', $appointment), [
                'payment_method_id' => $cash->id,
                'amount' => 2500,
                'product_items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect();

        $invoice = Invoice::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->assertDatabaseHas('sale_items', [
            'tenant_id' => $tenant->id,
            'sale_id' => $invoice->id,
            'item_type' => 'product',
            'product_id' => $product->id,
            'quantity' => 2,
            'total_amount' => 1500,
        ]);

        $this->assertDatabaseHas('inventories', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 3,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'type' => StockMovement::TYPE_POS_SALE,
            'quantity' => -2,
            'quantity_before' => 5,
            'quantity_after' => 3,
        ]);

        $this->actingAs($owner)
            ->post(route('billing.invoices.void', $invoice), ['void_reason' => 'Customer requested cancellation'])
            ->assertRedirect();

        $this->assertDatabaseHas('inventories', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 5,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'type' => StockMovement::TYPE_SALE_RETURN,
            'quantity' => 2,
            'quantity_before' => 3,
            'quantity_after' => 5,
        ]);
    }

    public function test_inventory_pages_are_tenant_isolated(): void
    {
        [$tenant, $owner, $branch] = $this->tenantSetup();
        $ownProduct = $this->productWithStock($tenant, $branch, 10);
        [$otherTenant, , $otherBranch] = $this->tenantSetup();
        $otherProduct = $this->productWithStock($otherTenant, $otherBranch, 8);

        $this->actingAs($owner)
            ->get(route('inventory.products.index'))
            ->assertOk()
            ->assertSee($ownProduct->name)
            ->assertDontSee($otherProduct->name);

        $this->actingAs($owner)
            ->get(route('inventory.stock.index'))
            ->assertOk()
            ->assertSee($ownProduct->sku)
            ->assertDontSee($otherProduct->sku);
    }

    public function test_receptionist_cannot_post_stock_adjustment(): void
    {
        [$tenant, , $branch] = $this->tenantSetup();
        $product = $this->productWithStock($tenant, $branch, 20);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('Receptionist');

        $this->actingAs($user)
            ->post(route('inventory.adjustments.store'), [
                'branch_id' => $branch->id,
                'reason' => 'damaged',
                'items' => [
                    ['product_id' => $product->id, 'actual_quantity' => 18],
                ],
            ])
            ->assertForbidden();
    }

    private function tenantSetup(): array
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
            'features' => [
                'appointment_calendar' => true,
                'customer_management' => true,
                'services' => true,
                'pos_billing' => true,
                'inventory' => true,
            ],
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

        $branch = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Colombo',
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

    private function productWithStock(Tenant $tenant, Branch $branch, int $quantity, int $cost = 500, int $selling = 1000): Product
    {
        $product = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Professional Shampoo ' . fake()->unique()->numberBetween(100, 999),
            'slug' => fake()->unique()->slug(),
            'sku' => 'SHAM-' . fake()->unique()->numberBetween(1000, 9999),
            'product_type' => Product::TYPE_RETAIL,
            'cost_price' => $cost,
            'selling_price' => $selling,
            'track_inventory' => true,
            'allow_negative_stock' => false,
            'is_sellable' => true,
            'is_consumable' => false,
            'is_active' => true,
            'reorder_level' => 5,
        ]);

        Inventory::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'quantity_on_hand' => $quantity,
            'quantity_reserved' => 0,
            'average_cost' => $cost,
        ]);

        return $product;
    }

    private function completedAppointment(Tenant $tenant, User $owner, Branch $branch): Appointment
    {
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'customer_code' => 'CUS-' . fake()->unique()->numberBetween(1000, 9999),
            'first_name' => 'Emma',
            'last_name' => 'Silva',
            'phone' => '0771234567',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $service = Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Hair Cut ' . fake()->unique()->numberBetween(100, 999),
            'slug' => fake()->unique()->slug(),
            'price' => 1000,
            'duration_minutes' => 60,
            'default_price' => 1000,
            'default_duration_minutes' => 60,
            'is_active' => true,
        ]);

        $staff = Staff::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'STF-' . fake()->unique()->numberBetween(1000, 9999),
            'first_name' => 'Nadeesha',
            'last_name' => 'Perera',
            'job_title' => 'Senior Stylist',
            'status' => Staff::STATUS_ACTIVE,
            'is_bookable' => true,
        ]);

        $appointment = Appointment::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'appointment_number' => 'APT-' . fake()->unique()->numberBetween(100000, 999999),
            'booking_source' => 'reception',
            'source' => 'reception',
            'status' => Appointment::STATUS_COMPLETED,
            'starts_at' => '2026-08-17 10:00',
            'ends_at' => '2026-08-17 11:00',
            'subtotal' => 1000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 1000,
            'completed_at' => now(),
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        AppointmentService::create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $appointment->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'service_name' => 'Hair Cut',
            'duration_minutes' => 60,
            'price' => 1000,
            'unit_price' => 1000,
            'discount_amount' => 0,
            'total_price' => 1000,
            'starts_at' => '2026-08-17 10:00',
            'ends_at' => '2026-08-17 11:00',
            'status' => Appointment::STATUS_COMPLETED,
        ]);

        return $appointment->fresh(['appointmentServices']);
    }
}
