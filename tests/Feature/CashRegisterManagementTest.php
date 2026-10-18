<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashRegisterShift;
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
use App\Services\Settings\SettingService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CashRegisterManagementTest extends TestCase
{
    use DatabaseTransactions;

    private Tenant $tenant;
    private User $owner;
    private Branch $branch;
    private Service $service;
    private Staff $stylist;
    private Product $product;
    private PaymentMethod $cash;
    private PaymentMethod $card;

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

    public function test_shift_reconciles_cash_sales_refunds_and_movements(): void
    {
        $this->actingAs($this->owner)
            ->get(route('billing.cash-register.index'))
            ->assertOk()
            ->assertSee('Open Shift');

        $this->actingAs($this->owner)
            ->post(route('billing.cash-register.open'), ['opening_cash' => 5000])
            ->assertSessionHasNoErrors();

        $shift = CashRegisterShift::withoutTenantScope()->where('branch_id', $this->branch->id)->firstOrFail();
        $this->assertTrue($shift->isOpen());
        $this->assertStringStartsWith('SHIFT-', $shift->shift_number);

        $cashSale = $this->sell([['payment_method_id' => $this->cash->id, 'amount' => 3000, 'cash_received' => 5000]]);
        $this->sell([['payment_method_id' => $this->card->id, 'amount' => 3000, 'transaction_reference' => 'AUTH-1']]);

        $this->actingAs($this->owner)
            ->post(route('billing.invoices.refunds.store', $cashSale), [
                'payment_method_id' => $this->cash->id,
                'amount' => 1000,
                'reason' => 'Partial refund',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->owner)
            ->post(route('billing.cash-register.movements.store', $shift), ['type' => 'cash_out', 'amount' => 500, 'reason' => 'Milk for tea'])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->owner)
            ->post(route('billing.cash-register.movements.store', $shift), ['type' => 'cash_in', 'amount' => 200, 'reason' => 'Change top-up'])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Payment::withoutTenantScope()->where('cash_register_shift_id', $shift->id)->count());

        // 5000 opening + 3000 cash sale - 1000 cash refund - 500 out + 200 in = 6700.
        $this->actingAs($this->owner)
            ->get(route('billing.cash-register.index'))
            ->assertOk()
            ->assertSee('LKR 6,700.00');

        $this->actingAs($this->owner)
            ->post(route('billing.cash-register.close', $shift), ['counted_cash' => 6650, 'closing_notes' => 'Counted twice'])
            ->assertRedirect(route('billing.cash-register.show', $shift))
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'short by LKR 50.00'));

        $shift->refresh();
        $this->assertSame(CashRegisterShift::STATUS_CLOSED, $shift->status);
        $this->assertEquals('6700.00', $shift->expected_cash);
        $this->assertEquals('6650.00', $shift->counted_cash);
        $this->assertEquals('-50.00', $shift->cash_difference);
        $this->assertEquals(3000, $shift->summary['cash_sales']);
        $this->assertEquals(6000, $shift->summary['payments_total']);

        $this->actingAs($this->owner)
            ->get(route('billing.cash-register.show', $shift))
            ->assertOk()
            ->assertSee('Shift Report ' . $shift->shift_number)
            ->assertSee('Card');
    }

    public function test_closed_shift_totals_are_frozen(): void
    {
        $shift = $this->openShift(1000);
        $invoice = $this->sell([['payment_method_id' => $this->cash->id, 'amount' => 3000]]);

        $this->actingAs($this->owner)->post(route('billing.cash-register.close', $shift), ['counted_cash' => 4000])->assertSessionHasNoErrors();

        $this->actingAs($this->owner)
            ->post(route('billing.invoices.void', $invoice), ['void_reason' => 'Entered twice'])
            ->assertSessionHasNoErrors();

        $this->assertEquals('4000.00', $shift->fresh()->expected_cash);
        $this->assertEquals(4000, $shift->fresh()->summary['expected_cash']);
    }

    public function test_only_one_shift_can_be_open_per_branch(): void
    {
        $this->openShift(1000);

        $this->actingAs($this->owner)
            ->post(route('billing.cash-register.open'), ['opening_cash' => 500])
            ->assertSessionHasErrors('shift');

        $this->assertSame(1, CashRegisterShift::withoutTenantScope()->where('branch_id', $this->branch->id)->count());
    }

    public function test_cash_out_cannot_exceed_expected_cash(): void
    {
        $shift = $this->openShift(300);

        $this->actingAs($this->owner)
            ->post(route('billing.cash-register.movements.store', $shift), ['type' => 'cash_out', 'amount' => 500, 'reason' => 'Too much'])
            ->assertSessionHasErrors('amount');
    }

    public function test_cash_is_blocked_without_an_open_shift_when_required(): void
    {
        app(SettingService::class)->setMany($this->tenant, null, ['pos.require_open_shift' => true], $this->owner);

        $this->actingAs($this->owner)
            ->get(route('billing.quick-sale.create'))
            ->assertOk()
            ->assertSee('No cash register shift is open');

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->salePayload([['payment_method_id' => $this->cash->id, 'amount' => 3000]]))
            ->assertSessionHasErrors('payments.0.payment_method_id');

        $this->assertFalse(Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->exists());

        // Card does not touch the drawer, so it is still allowed.
        $this->sell([['payment_method_id' => $this->card->id, 'amount' => 3000, 'transaction_reference' => 'AUTH-2']]);

        $this->openShift(0);
        $this->sell([['payment_method_id' => $this->cash->id, 'amount' => 3000]]);
    }

    public function test_cash_without_shift_is_allowed_by_default(): void
    {
        $invoice = $this->sell([['payment_method_id' => $this->cash->id, 'amount' => 3000]]);

        $this->assertNull($invoice->payments->first()->cash_register_shift_id);
    }

    public function test_bill_can_be_split_across_card_and_cash(): void
    {
        $invoice = $this->sell([
            ['payment_method_id' => $this->card->id, 'amount' => 2000, 'transaction_reference' => 'AUTH-3'],
            ['payment_method_id' => $this->cash->id, 'amount' => 1000, 'cash_received' => 1500],
        ]);

        $this->assertSame(Invoice::PAYMENT_PAID, $invoice->payment_status);
        $this->assertCount(2, $invoice->payments);
        $this->assertDatabaseHas('payments', ['sale_id' => $invoice->id, 'payment_method_id' => $this->card->id, 'amount' => 2000, 'transaction_reference' => 'AUTH-3']);
        $this->assertDatabaseHas('payments', ['sale_id' => $invoice->id, 'payment_method_id' => $this->cash->id, 'amount' => 1000, 'change_given' => 500]);
    }

    public function test_excess_in_a_split_comes_off_cash_as_change(): void
    {
        $invoice = $this->sell([
            ['payment_method_id' => $this->cash->id, 'amount' => 2000],
            ['payment_method_id' => $this->card->id, 'amount' => 2000, 'transaction_reference' => 'AUTH-4'],
        ]);

        $this->assertEquals('3000.00', $invoice->paid_amount);
        $this->assertDatabaseHas('payments', ['sale_id' => $invoice->id, 'payment_method_id' => $this->card->id, 'amount' => 2000]);
        $this->assertDatabaseHas('payments', ['sale_id' => $invoice->id, 'payment_method_id' => $this->cash->id, 'amount' => 1000, 'cash_received' => 2000, 'change_given' => 1000]);
    }

    public function test_non_cash_split_cannot_exceed_the_amount_due(): void
    {
        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->salePayload([
                ['payment_method_id' => $this->card->id, 'amount' => 2500, 'transaction_reference' => 'AUTH-5'],
                ['payment_method_id' => $this->card->id, 'amount' => 1000, 'transaction_reference' => 'AUTH-6'],
            ]))
            ->assertSessionHasErrors('payments');

        $this->assertFalse(Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->exists());
    }

    public function test_split_payment_respects_setting(): void
    {
        app(SettingService::class)->setMany($this->tenant, null, ['pos.allow_split_payment' => false], $this->owner);

        $this->actingAs($this->owner)
            ->get(route('billing.quick-sale.create'))
            ->assertOk()
            ->assertDontSee('Split Payment');

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->salePayload([
                ['payment_method_id' => $this->card->id, 'amount' => 2000, 'transaction_reference' => 'AUTH-7'],
                ['payment_method_id' => $this->cash->id, 'amount' => 1000],
            ]))
            ->assertSessionHasErrors('payments');
    }

    public function test_reference_is_required_per_split_line(): void
    {
        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->salePayload([
                ['payment_method_id' => $this->cash->id, 'amount' => 1000],
                ['payment_method_id' => $this->card->id, 'amount' => 2000],
            ]))
            ->assertSessionHasErrors('payments.1.transaction_reference');
    }

    public function test_day_end_report_totals_the_day(): void
    {
        $shift = $this->openShift(1000);
        $invoice = $this->sell([
            ['payment_method_id' => $this->card->id, 'amount' => 2000, 'transaction_reference' => 'AUTH-8'],
            ['payment_method_id' => $this->cash->id, 'amount' => 1000],
        ]);
        $this->actingAs($this->owner)->post(route('billing.invoices.refunds.store', $invoice), [
            'payment_method_id' => $this->cash->id,
            'amount' => 400,
            'reason' => 'Goodwill',
        ])->assertSessionHasNoErrors();
        $this->actingAs($this->owner)->post(route('billing.cash-register.close', $shift), ['counted_cash' => 1500])->assertSessionHasNoErrors();

        $this->actingAs($this->owner)
            ->get(route('billing.reports.day-end'))
            ->assertOk()
            ->assertSee('Day-End Report')
            ->assertSee('LKR 3,000.00')
            ->assertSee('LKR 400.00')
            ->assertSee('LKR 2,600.00')
            ->assertSee('Card')
            ->assertSee($shift->shift_number)
            // 1000 opening + 1000 cash - 400 cash refund = 1600 expected, 1500 counted.
            ->assertSee('- LKR 100.00');

        $yesterday = now($this->tenant->timezone)->subDay()->format('Y-m-d');

        $this->actingAs($this->owner)
            ->get(route('billing.reports.day-end', ['date' => $yesterday]))
            ->assertOk()
            ->assertDontSee($shift->shift_number)
            ->assertSee('No payments.');
    }

    public function test_day_end_report_requires_report_permission(): void
    {
        $cashier = User::factory()->create(['tenant_id' => $this->tenant->id, 'email_verified_at' => now()]);
        $cashier->givePermissionTo(['billing.view', 'billing.checkout', 'payments.create']);
        $cashier->branches()->attach($this->branch->id, ['tenant_id' => $this->tenant->id]);

        $this->actingAs($cashier)->get(route('billing.reports.day-end'))->assertForbidden();

        // A cashier still runs the drawer.
        $this->actingAs($cashier)->get(route('billing.cash-register.index'))->assertOk();
        $this->actingAs($cashier)->post(route('billing.cash-register.open'), ['opening_cash' => 100])->assertSessionHasNoErrors();
    }

    public function test_staff_without_payment_permission_cannot_use_the_register(): void
    {
        $stylistUser = User::factory()->create(['tenant_id' => $this->tenant->id, 'email_verified_at' => now()]);
        $stylistUser->givePermissionTo(['appointments.view']);

        $this->actingAs($stylistUser)->get(route('billing.cash-register.index'))->assertForbidden();
        $this->actingAs($stylistUser)->post(route('billing.cash-register.open'), ['opening_cash' => 100])->assertForbidden();
    }

    public function test_drawer_can_be_counted_by_denomination(): void
    {
        $shift = $this->openShift(4000);

        $this->actingAs($this->owner)
            ->get(route('billing.cash-register.index'))
            ->assertOk()
            ->assertSee('name="denominations[5000]"', false)
            ->assertSee('name="denominations[1]"', false);

        // The total is worked out from the counts, whatever was typed into the total box.
        $this->actingAs($this->owner)
            ->post(route('billing.cash-register.close', $shift), [
                'counted_cash' => 1,
                'denominations' => ['1000' => 3, '500' => 1, '100' => 4, '50' => 2, '7' => 99],
            ])
            ->assertSessionHasNoErrors();

        $shift->refresh();
        $this->assertEquals('4000.00', $shift->counted_cash);
        $this->assertEquals('0.00', $shift->cash_difference);
        $this->assertEquals([1000 => 3, 500 => 1, 100 => 4, 50 => 2], $shift->counted_denominations);

        $this->actingAs($this->owner)
            ->get(route('billing.cash-register.show', $shift))
            ->assertOk()
            ->assertSee('Cash Count')
            ->assertSee('1,000 × 3');
    }

    public function test_closing_needs_a_count(): void
    {
        $shift = $this->openShift(100);

        $this->actingAs($this->owner)
            ->post(route('billing.cash-register.close', $shift), ['denominations' => ['1000' => 0]])
            ->assertSessionHasErrors('counted_cash');

        $this->assertTrue($shift->fresh()->isOpen());
    }

    public function test_cash_expense_paid_from_drawer_reduces_expected_cash(): void
    {
        $shift = $this->openShift(5000);
        $category = $this->expenseCategory();

        $this->actingAs($this->owner)
            ->post(route('expense-management.expenses.store'), $this->expensePayload($category, 1200, ['from_cash_drawer' => 1]))
            ->assertSessionHasNoErrors();

        $payment = \App\Models\ExpensePayment::withoutTenantScope()->where('tenant_id', $this->tenant->id)->firstOrFail();
        $this->assertSame($shift->id, $payment->cash_register_shift_id);

        $this->actingAs($this->owner)
            ->get(route('billing.cash-register.index'))
            ->assertOk()
            ->assertSee('Expenses Paid from Drawer')
            ->assertSee('LKR 3,800.00');

        $this->actingAs($this->owner)->post(route('billing.cash-register.close', $shift), ['counted_cash' => 3800])->assertSessionHasNoErrors();
        $this->assertEquals('0.00', $shift->fresh()->cash_difference);

        $this->actingAs($this->owner)
            ->get(route('billing.reports.day-end'))
            ->assertOk()
            ->assertSee('Expenses paid from the cash drawer');
    }

    public function test_expense_paid_from_own_pocket_does_not_touch_the_drawer(): void
    {
        $shift = $this->openShift(5000);

        $this->actingAs($this->owner)
            ->post(route('expense-management.expenses.store'), $this->expensePayload($this->expenseCategory(), 1200, ['from_cash_drawer' => 0]))
            ->assertSessionHasNoErrors();

        $this->assertNull(\App\Models\ExpensePayment::withoutTenantScope()->where('tenant_id', $this->tenant->id)->value('cash_register_shift_id'));
        $this->actingAs($this->owner)->post(route('billing.cash-register.close', $shift), ['counted_cash' => 5000])->assertSessionHasNoErrors();
        $this->assertEquals('0.00', $shift->fresh()->cash_difference);
    }

    public function test_drawer_expense_is_refused_without_a_shift_and_nothing_is_saved(): void
    {
        $this->actingAs($this->owner)
            ->post(route('expense-management.expenses.store'), $this->expensePayload($this->expenseCategory(), 1200, ['from_cash_drawer' => 1]))
            ->assertSessionHasErrors('from_cash_drawer');

        $this->assertFalse(\App\Models\Expense::withoutTenantScope()->where('tenant_id', $this->tenant->id)->exists());
    }

    public function test_drawer_expense_must_be_cash_and_fit_in_the_drawer(): void
    {
        $this->openShift(500);
        $category = $this->expenseCategory();

        $this->actingAs($this->owner)
            ->post(route('expense-management.expenses.store'), $this->expensePayload($category, 200, ['from_cash_drawer' => 1, 'payment_method_id' => $this->card->id, 'payment_reference_number' => 'AUTH-9']))
            ->assertSessionHasErrors('from_cash_drawer');

        $this->actingAs($this->owner)
            ->post(route('expense-management.expenses.store'), $this->expensePayload($category, 800, ['from_cash_drawer' => 1]))
            ->assertSessionHasErrors('from_cash_drawer');
    }

    public function test_invoice_balance_can_be_collected_with_a_split_payment(): void
    {
        $invoice = $this->sell([['payment_method_id' => $this->card->id, 'amount' => 1000, 'transaction_reference' => 'AUTH-10']]);
        $this->assertSame(Invoice::PAYMENT_PARTIAL, $invoice->payment_status);

        $this->actingAs($this->owner)
            ->get(route('billing.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Split Payment')
            ->assertSee('Record Payment');

        $this->actingAs($this->owner)
            ->post(route('billing.invoices.payments.store', $invoice), [
                'payments' => [
                    ['payment_method_id' => $this->card->id, 'amount' => 1500, 'transaction_reference' => 'AUTH-11'],
                    ['payment_method_id' => $this->cash->id, 'amount' => 1000],
                ],
            ])
            ->assertSessionHasErrors('payments');

        $this->actingAs($this->owner)
            ->post(route('billing.invoices.payments.store', $invoice), [
                'payments' => [
                    ['payment_method_id' => $this->card->id, 'amount' => 1500, 'transaction_reference' => 'AUTH-12'],
                    ['payment_method_id' => $this->cash->id, 'amount' => 500, 'cash_received' => 1000],
                ],
                'payment_notes' => 'Balance settled',
            ])
            ->assertSessionHasNoErrors();

        $invoice->refresh();
        $this->assertSame(Invoice::PAYMENT_PAID, $invoice->payment_status);
        $this->assertCount(3, $invoice->payments);
        $this->assertDatabaseHas('payments', ['sale_id' => $invoice->id, 'payment_method_id' => $this->cash->id, 'amount' => 500, 'change_given' => 500, 'notes' => 'Balance settled']);
    }

    private function expenseCategory(): \App\Models\ExpenseCategory
    {
        return \App\Models\ExpenseCategory::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pantry',
            'code' => 'PANTRY',
            'is_active' => true,
        ]);
    }

    private function expensePayload(\App\Models\ExpenseCategory $category, float $amount, array $overrides = []): array
    {
        return array_merge([
            'branch_id' => $this->branch->id,
            'category_id' => $category->id,
            'expense_date' => now($this->tenant->timezone)->toDateString(),
            'description' => 'Tea and milk',
            'subtotal' => $amount,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'payment_method_id' => $this->cash->id,
            'paid_amount' => $amount,
            'payment_date' => now($this->tenant->timezone)->toDateString(),
        ], $overrides);
    }

    private function openShift(float $openingCash): CashRegisterShift
    {
        $this->actingAs($this->owner)
            ->post(route('billing.cash-register.open'), ['opening_cash' => $openingCash])
            ->assertSessionHasNoErrors();

        return CashRegisterShift::withoutTenantScope()
            ->where('branch_id', $this->branch->id)
            ->where('status', CashRegisterShift::STATUS_OPEN)
            ->firstOrFail();
    }

    private function salePayload(array $payments): array
    {
        return [
            'customer_mode' => 'walk_in',
            'service_items' => [['service_id' => $this->service->id, 'staff_id' => $this->stylist->id, 'quantity' => 1]],
            'product_items' => [],
            'payments' => $payments,
        ];
    }

    private function sell(array $payments): Invoice
    {
        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->salePayload($payments))
            ->assertSessionHasNoErrors();

        return Invoice::withoutTenantScope()->with('payments')->where('tenant_id', $this->tenant->id)->latest('id')->firstOrFail();
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
                'customer_management' => true,
                'services' => true,
                'pos_billing' => true,
                'expenses' => true,
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
        $this->service->branches()->attach($this->branch->id, ['tenant_id' => $this->tenant->id, 'price' => 3000, 'duration_minutes' => 45, 'is_active' => true]);

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
        $this->card = PaymentMethod::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Card',
            'code' => 'card',
            'type' => 'card',
            'is_active' => true,
            'requires_reference' => true,
        ]);
    }
}
