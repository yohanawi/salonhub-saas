<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Role;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DashboardKpiManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_salon_owner_can_see_tenant_kpi_dashboard(): void
    {
        [$tenant, $owner, $branch] = $this->salonOwner();
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'first_name' => 'Amali',
            'last_name' => 'Silva',
            'status' => Customer::STATUS_ACTIVE,
        ]);
        $staff = \App\Models\Staff::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Kasun',
            'last_name' => 'Perera',
            'status' => \App\Models\Staff::STATUS_ACTIVE,
            'is_bookable' => true,
        ]);
        $service = Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Hair Coloring',
            'slug' => 'hair-coloring-dashboard',
            'duration_minutes' => 90,
            'price' => 4500,
            'is_active' => true,
        ]);
        $product = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Shampoo',
            'sku' => 'SH-DASH',
            'cost_price' => 800,
            'selling_price' => 1200,
            'reorder_level' => 5,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'starts_at' => now()->setTime(10, 0),
            'ends_at' => now()->setTime(11, 30),
            'status' => Appointment::STATUS_COMPLETED,
            'booking_source' => 'reception',
        ]);
        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'appointment_id' => $appointment->id,
            'invoice_number' => 'INV-DASH-001',
            'subtotal' => 4500,
            'total' => 4500,
            'paid_amount' => 4500,
            'balance_amount' => 0,
            'status' => Invoice::STATUS_ISSUED,
            'payment_status' => Invoice::PAYMENT_PAID,
            'issued_at' => now(),
            'paid_at' => now(),
        ]);
        \App\Models\InvoiceItem::create([
            'tenant_id' => $tenant->id,
            'sale_id' => $invoice->id,
            'item_type' => Service::class,
            'item_id' => $service->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'item_name' => $service->name,
            'description' => $service->name,
            'quantity' => 1,
            'unit_price' => 4500,
            'gross_amount' => 4500,
            'net_amount' => 4500,
            'total_amount' => 4500,
            'total' => 4500,
        ]);
        Payment::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'sale_id' => $invoice->id,
            'customer_id' => $customer->id,
            'payment_number' => 'PAY-DASH-001',
            'amount' => 4500,
            'method' => 'cash',
            'status' => Payment::STATUS_COMPLETED,
            'paid_at' => now(),
            'received_by' => $owner->id,
        ]);
        Inventory::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'quantity_on_hand' => 2,
            'quantity_reserved' => 0,
            'average_cost' => 800,
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Salon Dashboard')
            ->assertSee('LKR 4,500.00')
            ->assertSee('Hair Coloring')
            ->assertSee('Low Stock Alerts');
    }

    public function test_super_admin_sees_platform_dashboard_by_default(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin = User::factory()->create(['tenant_id' => null]);
        $superAdmin->assignRole($superAdminRole);

        $tenant = Tenant::create([
            'name' => 'Platform Salon',
            'slug' => 'platform-salon-' . fake()->unique()->numberBetween(1000, 9999),
            'status' => 'active',
            'currency' => 'LKR',
            'timezone' => 'Asia/Colombo',
        ]);
        $plan = Plan::create([
            'name' => 'Professional',
            'slug' => 'professional-dashboard-' . fake()->unique()->numberBetween(1000, 9999),
            'price' => 6990,
            'billing_period' => 'monthly',
            'is_active' => true,
        ]);
        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'price' => 6990,
            'billing_period' => 'monthly',
            'starts_at' => now(),
        ]);

        $this->actingAs($superAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Platform Dashboard')
            ->assertSee('MRR')
            ->assertSee('LKR 6,990.00');
    }

    public function test_dashboard_today_follows_the_salons_calendar_day(): void
    {
        // 20:00 UTC on Oct 18 is already 01:30 on Oct 19 in Colombo.
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-10-18 20:00:00', 'UTC'));
        [$tenant, $owner, $branch] = $this->salonOwner();

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'invoice_number' => 'INV-TZ-001',
            'subtotal' => 1500,
            'total' => 1500,
            'paid_amount' => 1500,
            'balance_amount' => 0,
            'status' => Invoice::STATUS_ISSUED,
            'payment_status' => Invoice::PAYMENT_PAID,
            'issued_at' => now(),
        ]);

        foreach ([['2026-10-18 19:00:00', 1000], ['2026-10-18 18:00:00', 500]] as $index => [$paidAt, $amount]) {
            Payment::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'sale_id' => $invoice->id,
                'payment_number' => 'PAY-TZ-00' . $index,
                'amount' => $amount,
                'method' => 'cash',
                'status' => Payment::STATUS_COMPLETED,
                'paid_at' => \Carbon\CarbonImmutable::parse($paidAt, 'UTC'),
            ]);
        }

        $category = \App\Models\ExpenseCategory::create(['tenant_id' => $tenant->id, 'name' => 'Pantry', 'code' => 'PANTRY', 'is_active' => true]);

        foreach ([['2026-10-19', 300], ['2026-10-18', 700]] as $index => [$date, $amount]) {
            \App\Models\Expense::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'expense_number' => 'EXP-TZ-00' . $index,
                'category_id' => $category->id,
                'expense_date' => $date,
                'description' => 'Pantry',
                'amount' => $amount,
                'subtotal' => $amount,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $amount,
                'paid_amount' => 0,
                'balance_amount' => $amount,
                'currency' => 'LKR',
                'payment_status' => \App\Models\Expense::PAYMENT_UNPAID,
                'approval_status' => \App\Models\Expense::APPROVAL_PENDING,
                'expense_status' => \App\Models\Expense::STATUS_CONFIRMED,
            ]);
        }

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'first_name' => 'Amali',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        // Appointment times are the salon's wall-clock time.
        foreach (['2026-10-19 10:00:00', '2026-10-18 22:00:00'] as $index => $startsAt) {
            Appointment::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'customer_id' => $customer->id,
                'appointment_number' => 'APT-TZ-00' . $index,
                'starts_at' => $startsAt,
                'ends_at' => \Carbon\CarbonImmutable::parse($startsAt)->addHour(),
                'status' => Appointment::STATUS_CONFIRMED,
                'booking_source' => 'reception',
            ]);
        }

        $dashboard = $this->actingAs($owner)->get(route('dashboard'))->assertOk()->viewData('dashboard');

        $this->assertEquals(1000.0, $dashboard['summary']['revenue']);
        $this->assertEquals(300.0, $dashboard['summary']['expenses']);
        $this->assertSame(1, collect($dashboard['cards'])->firstWhere('label', 'Appointments')['value']);
        $this->assertSame(['Oct 19'], $dashboard['charts']['revenue']['labels']);
        $this->assertSame([1000.0], $dashboard['charts']['revenue']['series']);
    }

    private function salonOwner(): array
    {
        $ownerRole = Role::firstOrCreate(['name' => 'Salon Owner', 'guard_name' => 'web']);
        $tenant = Tenant::create([
            'name' => 'Glow Beauty Lounge',
            'slug' => 'glow-dashboard-' . fake()->unique()->numberBetween(1000, 9999),
            'status' => 'active',
            'currency' => 'LKR',
            'timezone' => 'Asia/Colombo',
        ]);
        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
            'onboarded_at' => now(),
        ]);
        $owner->assignRole($ownerRole);
        $branch = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Branch',
            'code' => 'MAIN-' . fake()->unique()->numberBetween(1000, 9999),
            'status' => Branch::STATUS_ACTIVE,
            'is_active' => true,
            'is_main' => true,
        ]);

        return [$tenant, $owner, $branch];
    }
}
