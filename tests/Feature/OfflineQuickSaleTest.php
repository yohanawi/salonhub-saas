<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Role;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Settings\SettingService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OfflineQuickSaleTest extends TestCase
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

    public function test_the_same_sale_submitted_twice_creates_one_invoice(): void
    {
        $payload = $this->payload(['client_uuid' => (string) Str::uuid()]);

        $this->actingAs($this->owner)->post(route('billing.quick-sale.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $payload)
            ->assertRedirect()
            ->assertSessionHas('status', fn (string $status) => str_contains($status, 'already saved'));

        $this->assertSame(1, Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->count());
    }

    public function test_offline_sale_syncs_as_json_and_is_marked_and_audited(): void
    {
        $uuid = (string) Str::uuid();

        $response = $this->actingAs($this->owner)
            ->postJson(route('billing.quick-sale.store'), $this->payload([
                'client_uuid' => $uuid,
                'offline' => 1,
                'offline_reference' => 'OFF-261018-AB12',
                'offline_created_at' => now()->subHour()->toIso8601String(),
            ]))
            ->assertCreated()
            ->assertJsonStructure(['invoice_id', 'invoice_number', 'url'])
            ->assertJson(['duplicate' => false]);

        $invoice = Invoice::withoutTenantScope()->findOrFail($response->json('invoice_id'));

        $this->assertSame($uuid, $invoice->client_uuid);
        $this->assertSame('OFF-261018-AB12', $invoice->offline_reference);
        $this->assertNotNull($invoice->offline_created_at);
        $this->assertSame(Invoice::PAYMENT_PAID, $invoice->payment_status);
        $this->assertTrue(AuditLog::withoutTenantScope()->where('tenant_id', $this->tenant->id)->where('action', 'offline_sale_synced')->exists());

        // A second sync of the same sale answers with the same invoice.
        $this->actingAs($this->owner)
            ->postJson(route('billing.quick-sale.store'), $this->payload(['client_uuid' => $uuid, 'offline' => 1]))
            ->assertOk()
            ->assertJson(['duplicate' => true, 'invoice_id' => $invoice->id]);
    }

    public function test_a_resync_after_the_stock_sold_out_still_returns_the_saved_invoice(): void
    {
        $uuid = (string) Str::uuid();
        $payload = $this->payload([
            'client_uuid' => $uuid,
            'offline' => 1,
            'service_items' => [],
            'product_items' => [['product_id' => $this->product->id, 'quantity' => 2]],
            'amount' => 1500,
        ]);

        $this->actingAs($this->owner)->postJson(route('billing.quick-sale.store'), $payload)->assertCreated();
        $this->assertSame(0, (int) Inventory::withoutTenantScope()->where('product_id', $this->product->id)->value('quantity_on_hand'));

        $this->actingAs($this->owner)
            ->postJson(route('billing.quick-sale.store'), $payload)
            ->assertOk()
            ->assertJson(['duplicate' => true]);
    }

    public function test_rejected_offline_sale_returns_json_errors(): void
    {
        $this->actingAs($this->owner)
            ->postJson(route('billing.quick-sale.store'), $this->payload([
                'client_uuid' => (string) Str::uuid(),
                'offline' => 1,
                'service_items' => [],
                'product_items' => [['product_id' => $this->product->id, 'quantity' => 5]],
                'amount' => 3750,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('product_items.0.quantity');

        $this->assertSame(0, Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->count());
    }

    public function test_offline_sale_does_not_need_the_cashier_pin_but_online_sale_does(): void
    {
        app(SettingService::class)->setMany($this->tenant, null, ['staff.require_pin_for_pos' => true], $this->owner);

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload())
            ->assertSessionHasErrors('cashier_pin');

        $this->actingAs($this->owner)
            ->postJson(route('billing.quick-sale.store'), $this->payload(['client_uuid' => (string) Str::uuid(), 'offline' => 1]))
            ->assertCreated();

        $this->assertNull(Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->value('cashier_staff_id'));
    }

    public function test_offline_time_in_the_future_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->postJson(route('billing.quick-sale.store'), $this->payload([
                'client_uuid' => (string) Str::uuid(),
                'offline' => 1,
                'offline_created_at' => now()->addDay()->toIso8601String(),
            ]))
            ->assertJsonValidationErrors('offline_created_at');
    }

    public function test_sync_token_and_offline_assets_are_served(): void
    {
        $this->actingAs($this->owner)
            ->getJson(route('billing.quick-sale.token'))
            ->assertOk()
            ->assertJsonStructure(['token']);

        $this->actingAs($this->owner)
            ->get(route('billing.quick-sale.create'))
            ->assertOk()
            ->assertSee('name="client_uuid"', false)
            ->assertSee('offline-sales-panel', false)
            ->assertSee('js/pos-offline.js', false);

        $this->assertFileExists(public_path('pos-sw.js'));
        $this->assertFileExists(public_path('js/pos-offline.js'));
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
            'duration_minutes' => 60,
            'default_price' => 3000,
            'default_duration_minutes' => 60,
            'is_active' => true,
        ]);
        $this->service->branches()->attach($this->branch->id, ['tenant_id' => $this->tenant->id, 'price' => 3000, 'duration_minutes' => 60, 'is_active' => true]);

        $this->stylist = Staff::create([
            'tenant_id' => $this->tenant->id,
            'employee_code' => 'STF-0001',
            'first_name' => 'Nadeesha',
            'last_name' => 'Perera',
            'job_title' => 'Stylist',
            'status' => Staff::STATUS_ACTIVE,
            'is_bookable' => true,
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
            'quantity' => 2,
            'quantity_on_hand' => 2,
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
