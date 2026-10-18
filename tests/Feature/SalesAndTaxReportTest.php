<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\Staff;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Billing\GiftCardService;
use App\Services\Billing\PackageService;
use App\Services\Reports\SalesReportService;
use App\Services\Reports\TaxReportService;
use App\Services\Settings\SettingService;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SalesAndTaxReportTest extends TestCase
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

    // ---------------------------------------------------------------- Sales report

    public function test_sales_are_split_by_staff_service_and_product_and_add_up_to_the_invoices(): void
    {
        $this->sale([
            'service_items' => [['service_id' => $this->service->id, 'staff_id' => $this->stylist->id, 'quantity' => 1]],
            'product_items' => [['product_id' => $this->product->id, 'quantity' => 2, 'staff_id' => $this->stylist->id]],
            'discount_amount' => 450,
            'tips' => [['staff_id' => $this->stylist->id, 'amount' => 200]],
            'amount' => 4250,
        ]);

        $report = $this->salesReport();

        // 3000 service + 1500 product - 450 invoice discount = 4050, shared 2/3 : 1/3.
        $this->assertSame(4050.0, $report['summary']['net_sales']);
        $this->assertSame(450.0, $report['summary']['discounts']);
        $this->assertSame(200.0, $report['summary']['tips']);
        $this->assertSame(1, $report['summary']['invoices']);

        $staff = $report['staff']->first();
        $this->assertSame('Nadeesha Perera', $staff['name']);
        $this->assertSame(1, $staff['services_count']);
        $this->assertSame(2700.0, $staff['service_sales']);
        $this->assertSame(1350.0, $staff['product_sales']);
        $this->assertSame(200.0, $staff['tips']);
        // Commission records are earned on line value before the invoice-level discount.
        $this->assertSame(450.0, $staff['commission']);

        $this->assertSame(['Hair Cut', 1, 2700.0], [$report['services'][0]['name'], $report['services'][0]['quantity'], $report['services'][0]['net']]);
        $this->assertSame(['Argan Shampoo', 2, 1350.0], [$report['products'][0]['name'], $report['products'][0]['quantity'], $report['products'][0]['net']]);
        $this->assertSame(4050.0, $report['months']->sum('net_sales'));
        $this->assertSame(4250.0, (float) $report['payments']->sum('total'));
    }

    public function test_void_invoices_are_left_out_and_gift_cards_are_not_sales(): void
    {
        $this->sale();
        $this->actingAs($this->owner)->post(route('billing.invoices.void', $this->invoice()), ['void_reason' => 'Mistake']);
        $this->sale();

        app(GiftCardService::class)->sell($this->branch, null, [
            'amount' => 5000,
            'payments' => [['payment_method_id' => $this->cash->id, 'amount' => 5000]],
        ], $this->owner);

        $report = $this->salesReport();

        $this->assertSame(3000.0, $report['summary']['net_sales']);
        $this->assertSame(1, $report['summary']['invoices'] - 1); // the gift card invoice is counted, its value is not sales
        $this->assertSame(5000.0, $report['summary']['gift_cards_sold']);
        $this->assertSame(5000.0, $report['prepaid']['gift_card_liability']);
    }

    public function test_sales_report_page_and_csv_export(): void
    {
        $this->sale();

        $this->actingAs($this->owner)
            ->get(route('billing.reports.sales'))
            ->assertOk()
            ->assertSee('Sales Report')
            ->assertSee('Nadeesha Perera')
            ->assertSee('Hair Cut');

        $csv = $this->actingAs($this->owner)
            ->get(route('billing.reports.sales.export', ['type' => 'staff']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->streamedContent();

        $this->assertStringContainsString('Staff,"Services Done"', $csv);
        $this->assertStringContainsString('"Nadeesha Perera",1,3000,0,3000,0,300', $csv);

        $this->actingAs($this->owner)
            ->get(route('billing.reports.sales', ['from' => '2026-05-01', 'to' => '2026-01-01']))
            ->assertSessionHasErrors('to');
    }

    // ---------------------------------------------------------------- Tax report

    public function test_output_vat_is_reported_on_the_discounted_value(): void
    {
        $this->enableVat(18);

        $this->sale(['discount_amount' => 500, 'amount' => 2950]);

        $report = $this->taxReport();
        $vat = $report['rates']->first();

        $this->assertSame('VAT', $vat['name']);
        $this->assertSame(18.0, $vat['rate']);
        $this->assertSame(2500.0, $vat['taxable']);
        $this->assertSame(450.0, $vat['tax']);
        $this->assertSame(450.0, $report['totals']['tax']);
        $this->assertSame(2500.0, $report['sscl']['turnover']);
    }

    public function test_tax_inclusive_prices_report_the_tax_inside_the_price(): void
    {
        $this->enableVat(18, inclusive: true);

        $this->sale(['amount' => 3000]);

        $vat = $this->taxReport()['rates']->first();

        $this->assertSame(457.63, $vat['tax']);
        $this->assertEqualsWithDelta(2542.37, $vat['taxable'], 0.02);
    }

    public function test_refunds_are_credit_notes_in_the_month_they_are_paid(): void
    {
        $this->enableVat(18);
        $this->sale(['amount' => 3540]);
        $invoice = $this->invoice();

        Refund::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'sale_id' => $invoice->id,
            'payment_method_id' => $this->cash->id,
            'refund_number' => 'REF-TEST-1',
            'amount' => 1770,
            'reason' => 'Unhappy',
            'status' => Refund::STATUS_COMPLETED,
            'requested_by' => $this->owner->id,
            'processed_at' => now(),
        ]);

        $vat = $this->taxReport()['rates']->first();

        $this->assertSame(540.0, $vat['tax']);
        $this->assertSame(270.0, $vat['credit_tax']);
        $this->assertSame(270.0, $vat['net_tax']);
        $this->assertSame(1500.0, $vat['net_taxable']);
    }

    public function test_an_invoice_voided_in_a_later_month_is_reversed_in_that_month(): void
    {
        $this->enableVat(18);
        $this->sale(['amount' => 3540]);
        $invoice = $this->invoice();
        $lastMonth = CarbonImmutable::now('Asia/Colombo')->subMonthNoOverflow()->startOfMonth()->addDays(10);
        $invoice->update(['issued_at' => $lastMonth]);

        $this->actingAs($this->owner)->post(route('billing.invoices.void', $invoice), ['void_reason' => 'Late void'])->assertSessionHasNoErrors();

        $filed = $this->taxReport($lastMonth);
        $current = $this->taxReport();

        // Last month's return still shows the sale; this month carries the reversal.
        $this->assertSame(540.0, $filed['totals']['tax']);
        $this->assertSame(540.0, $current['rates']->first()['voided_tax']);
        $this->assertSame(-540.0, $current['totals']['tax']);
        $this->assertSame(1, $current['counts']['voided']);
    }

    public function test_gift_cards_are_outside_vat_and_packages_are_taxed_when_sold(): void
    {
        $this->enableVat(18);

        app(GiftCardService::class)->sell($this->branch, null, [
            'amount' => 5000,
            'payments' => [['payment_method_id' => $this->cash->id, 'amount' => 5000]],
        ], $this->owner);

        $package = ServicePackage::create(['tenant_id' => $this->tenant->id, 'name' => 'Cut x3', 'price' => 8000, 'is_active' => true]);
        $package->items()->create(['tenant_id' => $this->tenant->id, 'service_id' => $this->service->id, 'quantity' => 3]);

        $customer = \App\Models\Customer::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUS-000001',
            'first_name' => 'Emma',
            'phone' => '0771234567',
            'status' => \App\Models\Customer::STATUS_ACTIVE,
        ]);

        $sold = app(PackageService::class)->sell($this->branch, $customer, $package, [
            'payments' => [['payment_method_id' => $this->cash->id, 'amount' => 9440]],
        ], $this->owner);

        $this->assertEquals('1440.00', $sold->invoice->tax);
        $this->assertSame(Invoice::PAYMENT_PAID, $sold->invoice->payment_status);

        $report = $this->taxReport();

        $this->assertSame(1440.0, $report['totals']['tax']);
        $this->assertSame(8000.0, $report['totals']['taxable']);
        $this->assertSame(5000.0, $report['outside_scope']['gift_cards_sold']);
    }

    public function test_sscl_is_worked_on_turnover_when_enabled(): void
    {
        app(SettingService::class)->setMany($this->tenant, null, ['tax.sscl_enabled' => true, 'tax.sscl_rate' => 2.5], $this->owner);

        $this->sale([
            'product_items' => [['product_id' => $this->product->id, 'quantity' => 2]],
            'amount' => 4500,
        ]);

        $sscl = $this->taxReport()['sscl'];

        $this->assertTrue($sscl['enabled']);
        $this->assertSame(3000.0, $sscl['service_turnover']);
        $this->assertSame(1500.0, $sscl['product_turnover']);
        $this->assertSame(112.5, $sscl['levy']);
    }

    public function test_tax_report_page_and_invoice_csv(): void
    {
        $this->enableVat(18);
        $this->sale(['amount' => 3540]);
        $month = now('Asia/Colombo')->format('Y-m');

        $this->actingAs($this->owner)
            ->get(route('billing.reports.tax', ['month' => $month]))
            ->assertOk()
            ->assertSee('Output Tax by Rate')
            ->assertSee('VAT 18%')
            ->assertSee('540.00');

        $csv = $this->actingAs($this->owner)
            ->get(route('billing.reports.tax.export', ['month' => $month]))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString($this->invoice()->invoice_number . ',', $csv);
        $this->assertStringContainsString(',3000,0,540,3540', $csv);
    }

    public function test_reports_need_the_report_permission(): void
    {
        Role::firstOrCreate(['name' => 'Beautician', 'guard_name' => 'web'])->givePermissionTo(['appointments.view']);
        $beautician = User::factory()->create(['tenant_id' => $this->tenant->id, 'email_verified_at' => now()]);
        $beautician->assignRole('Beautician');

        $this->actingAs($beautician)->get(route('billing.reports.sales'))->assertForbidden();
        $this->actingAs($beautician)->get(route('billing.reports.tax'))->assertForbidden();
        $this->actingAs($beautician)->get(route('billing.reports.sales.export', ['type' => 'staff']))->assertForbidden();
    }

    // ---------------------------------------------------------------- Helpers

    private function sale(array $overrides = []): void
    {
        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), array_merge([
                'customer_mode' => 'walk_in',
                'service_items' => [['service_id' => $this->service->id, 'staff_id' => $this->stylist->id, 'quantity' => 1]],
                'product_items' => [],
                'payment_method_id' => $this->cash->id,
                'amount' => 3000,
            ], $overrides))
            ->assertSessionHasNoErrors();
    }

    private function invoice(): Invoice
    {
        return Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->latest('id')->firstOrFail();
    }

    private function salesReport(): array
    {
        $today = CarbonImmutable::now('Asia/Colombo');

        return app(SalesReportService::class)->build($this->tenant, [$this->branch->id], $today->startOfMonth(), $today);
    }

    private function taxReport(?CarbonImmutable $month = null): array
    {
        return app(TaxReportService::class)->build($this->tenant, [$this->branch->id], $month ?? CarbonImmutable::now('Asia/Colombo'));
    }

    private function enableVat(float $rate, bool $inclusive = false): void
    {
        app(SettingService::class)->setMany($this->tenant, null, [
            'tax.enabled' => true,
            'tax.name' => 'VAT',
            'tax.rate' => $rate,
            'tax.prices_include_tax' => $inclusive,
        ], $this->owner);
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
            'quantity' => 10,
            'quantity_on_hand' => 10,
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
