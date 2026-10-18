<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\Payment;
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
use App\Services\Billing\PosPinService;
use App\Services\Settings\SettingService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PosCashierAndReceiptTest extends TestCase
{
    use DatabaseTransactions;

    private Tenant $tenant;
    private User $owner;
    private Branch $branch;
    private Service $service;
    private Staff $stylist;
    private Staff $cashier;
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

    public function test_pos_pin_is_stored_as_a_hash(): void
    {
        $this->cashier->setPosPin('4321');
        $this->cashier->refresh();

        $this->assertNotSame('4321', $this->cashier->pos_pin);
        $this->assertTrue($this->cashier->checkPosPin('4321'));
        $this->assertFalse($this->cashier->checkPosPin('1234'));
        $this->assertArrayNotHasKey('pos_pin', $this->cashier->toArray());

        $this->cashier->setPosPin(null);

        $this->assertFalse($this->cashier->fresh()->hasPosPin());
    }

    public function test_sale_without_pin_setting_records_the_users_linked_staff_as_cashier(): void
    {
        $this->cashier->update(['user_id' => $this->owner->id]);

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $invoice = $this->invoice();

        $this->assertSame($this->cashier->id, $invoice->cashier_staff_id);
        $this->assertSame($this->cashier->id, Payment::withoutTenantScope()->where('sale_id', $invoice->id)->value('cashier_staff_id'));
    }

    public function test_sale_is_rejected_without_a_pin_when_required(): void
    {
        $this->requirePin();

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload())
            ->assertSessionHasErrors('cashier_pin');

        $this->assertSame(0, Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->count());
    }

    public function test_wrong_pin_is_rejected_and_audited(): void
    {
        $this->requirePin();

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload(['cashier_staff_id' => $this->cashier->id, 'cashier_pin' => '0000']))
            ->assertSessionHasErrors('cashier_pin');

        $this->assertSame(0, Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->count());
        $this->assertTrue(AuditLog::withoutTenantScope()->where('tenant_id', $this->tenant->id)->where('action', 'pos_pin_failed')->exists());
    }

    public function test_correct_pin_records_the_cashier_on_invoice_and_payment(): void
    {
        $this->requirePin();

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload(['cashier_staff_id' => $this->cashier->id, 'cashier_pin' => '4321']))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $invoice = $this->invoice();

        $this->assertSame($this->cashier->id, $invoice->cashier_staff_id);
        $this->assertSame($this->cashier->id, Payment::withoutTenantScope()->where('sale_id', $invoice->id)->value('cashier_staff_id'));

        $this->actingAs($this->owner)
            ->get(route('billing.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Cashier: Kasun Fernando');
    }

    public function test_staff_from_another_branch_cannot_act_as_cashier(): void
    {
        $this->requirePin();

        $otherBranch = Branch::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Kandy',
            'code' => 'KDY',
            'status' => Branch::STATUS_ACTIVE,
            'is_active' => true,
            'is_main' => false,
            'country' => 'LK',
            'currency' => 'LKR',
            'timezone' => 'Asia/Colombo',
        ]);
        $outsider = $this->staffAt($otherBranch, 'STF-0009', 'Ruwan');
        $outsider->setPosPin('5555');

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload(['cashier_staff_id' => $outsider->id, 'cashier_pin' => '5555']))
            ->assertSessionHasErrors('cashier_pin');
    }

    public function test_pin_entry_locks_after_too_many_wrong_attempts(): void
    {
        $this->requirePin();

        for ($attempt = 0; $attempt < PosPinService::MAX_ATTEMPTS; $attempt++) {
            $this->actingAs($this->owner)
                ->post(route('billing.quick-sale.store'), $this->payload(['cashier_staff_id' => $this->cashier->id, 'cashier_pin' => '0000']));
        }

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload(['cashier_staff_id' => $this->cashier->id, 'cashier_pin' => '4321']))
            ->assertSessionHasErrors(['cashier_pin' => 'Too many incorrect PIN attempts. Try again in 5 minutes.']);

        $this->assertSame(0, Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->count());
    }

    public function test_collecting_a_balance_requires_the_pin_when_enabled(): void
    {
        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload(['amount' => 1000]))
            ->assertSessionHasNoErrors();

        $invoice = $this->invoice();
        $this->assertEquals('2000.00', $invoice->balance_amount);

        $this->requirePin();

        $this->actingAs($this->owner)
            ->post(route('billing.invoices.payments.store', $invoice), ['payment_method_id' => $this->cash->id, 'amount' => 2000])
            ->assertSessionHasErrors('cashier_pin');

        $this->actingAs($this->owner)
            ->post(route('billing.invoices.payments.store', $invoice), [
                'payment_method_id' => $this->cash->id,
                'amount' => 2000,
                'cashier_staff_id' => $this->cashier->id,
                'cashier_pin' => '4321',
            ])
            ->assertSessionHasNoErrors();

        $this->assertEquals('0.00', $invoice->fresh()->balance_amount);
        $this->assertSame(1, Payment::withoutTenantScope()->where('sale_id', $invoice->id)->where('cashier_staff_id', $this->cashier->id)->count());
    }

    public function test_quick_sale_page_shows_pin_prompt_and_barcode_scanner(): void
    {
        $this->requirePin();

        $this->actingAs($this->owner)
            ->get(route('billing.quick-sale.create'))
            ->assertOk()
            ->assertSee('Cashier PIN')
            ->assertSee('Kasun Fernando')
            ->assertSee('id="quick_sale_scan"', false)
            ->assertSee('data-barcode="4790000000017"', false)
            ->assertSee('data-sku="SHAM-1001"', false);
    }

    public function test_thermal_receipt_renders_for_the_invoice(): void
    {
        $this->cashier->update(['user_id' => $this->owner->id]);

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload(['cash_received' => 5000]))
            ->assertSessionHasNoErrors();

        $invoice = $this->invoice();

        $this->actingAs($this->owner)
            ->get(route('billing.invoices.receipt', $invoice))
            ->assertOk()
            ->assertSee('size: 80mm auto', false)
            ->assertSee($invoice->invoice_number)
            ->assertSee('Hair Cut')
            ->assertSee('Kasun Fernando')
            ->assertSee('3,000.00')
            ->assertSee('2,000.00')
            ->assertDontSee('window.print();', false);

        $this->actingAs($this->owner)
            ->get(route('billing.invoices.receipt', ['invoice' => $invoice, 'print' => 1]))
            ->assertOk()
            ->assertSee('window.print();', false);
    }

    public function test_thermal_receipt_of_another_salon_is_not_visible(): void
    {
        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $invoice = $this->invoice();
        $stranger = User::factory()->create([
            'tenant_id' => Tenant::create([
                'name' => 'Other Salon',
                'slug' => fake()->unique()->slug(),
                'email' => fake()->unique()->safeEmail(),
                'status' => 'active',
            ])->id,
            'email_verified_at' => now(),
        ]);
        $stranger->assignRole('Salon Owner');

        $this->assertContains(
            $this->actingAs($stranger)->get(route('billing.invoices.receipt', $invoice))->status(),
            [403, 404]
        );
    }

    public function test_receipt_auto_prints_once_after_a_sale_when_enabled(): void
    {
        app(SettingService::class)->setMany($this->tenant, null, [
            'pos.auto_print_receipt' => true,
            'invoice.receipt_format' => 'thermal_80mm',
        ], $this->owner);

        $this->actingAs($this->owner)
            ->followingRedirects()
            ->post(route('billing.quick-sale.store'), $this->payload())
            ->assertOk()
            ->assertSee('setTimeout(() => trigger?.click(), 300)', false)
            ->assertSee('[data-thermal-print]', false);

        $invoice = $this->invoice();

        $this->actingAs($this->owner)
            ->get(route('billing.invoices.show', $invoice))
            ->assertOk()
            ->assertDontSee('setTimeout(() => trigger?.click(), 300)', false);
    }

    private function requirePin(): void
    {
        app(SettingService::class)->setMany($this->tenant, null, ['staff.require_pin_for_pos' => true], $this->owner);
    }

    private function invoice(): Invoice
    {
        return Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->latest('id')->firstOrFail();
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

    private function staffAt(Branch $branch, string $code, string $firstName, string $lastName = 'Perera'): Staff
    {
        $staff = Staff::create([
            'tenant_id' => $this->tenant->id,
            'employee_code' => $code,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'job_title' => 'Stylist',
            'status' => Staff::STATUS_ACTIVE,
            'is_bookable' => true,
            'commission_type' => 'percentage',
            'commission_value' => 10,
        ]);

        $staff->branches()->attach($branch->id, [
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

        $this->stylist = $this->staffAt($this->branch, 'STF-0001', 'Nadeesha');
        $this->stylist->services()->attach($this->service->id, [
            'tenant_id' => $this->tenant->id,
            'status' => 'active',
        ]);

        $this->cashier = $this->staffAt($this->branch, 'STF-0002', 'Kasun', 'Fernando');
        $this->cashier->setPosPin('4321');

        $this->product = Product::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Argan Shampoo',
            'slug' => 'argan-shampoo',
            'sku' => 'SHAM-1001',
            'barcode' => '4790000000017',
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
