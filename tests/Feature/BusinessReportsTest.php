<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentFeedback;
use App\Models\CommissionPayout;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Refund;
use App\Models\ReportSchedule;
use App\Models\StockMovement;
use App\Notifications\ScheduledReportNotification;
use App\Services\Reports\ProfitLossService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Concerns\BuildsSalonFixtures;
use Tests\TestCase;

class BusinessReportsTest extends TestCase
{
    use BuildsSalonFixtures;
    use DatabaseTransactions;

    private const FEATURES = ['basic_reports', 'advanced_reports', 'multi_branch_reports', 'expenses', 'payroll', 'loyalty_membership', 'promotions_discounts'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_profit_and_loss_adds_up_every_source(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon(self::FEATURES);
        $this->seedFinancials($tenant, $owner, $branch);

        $from = CarbonImmutable::now('Asia/Colombo')->startOfMonth();
        $to = CarbonImmutable::now('Asia/Colombo')->endOfMonth();
        $report = app(ProfitLossService::class)->build($tenant, [$branch->id], $from, $to);

        $this->assertEquals(9000.0, collect($report['income'])->firstWhere('type', 'service')['amount']);
        $this->assertEquals(2000.0, collect($report['income'])->firstWhere('type', 'product')['amount']);
        $this->assertEquals(11000.0, $report['revenue']);
        $this->assertEquals(500.0, $report['refunds']);
        $this->assertEquals(10500.0, $report['net_revenue']);
        $this->assertEquals(800.0, $report['cogs']['total']);
        $this->assertEquals(9700.0, $report['gross_profit']);
        $this->assertEquals(1500.0, $report['expense_total']);
        $this->assertEquals(4800.0, $report['staff']['payroll']);
        $this->assertEquals(750.0, $report['staff']['statutory']);
        $this->assertEquals(300.0, $report['staff']['commission_payouts']);
        $this->assertEquals(2350.0, $report['net_profit']);
        $this->assertEquals(165.0, $report['tax_collected']);

        $this->actingAs($owner)->get(route('reports.profit-loss', ['from' => $from->toDateString(), 'to' => $to->toDateString()]))
            ->assertOk()->assertSee('Profit & Loss')->assertSee('LKR 2,350.00')->assertSee('Rent');
    }

    public function test_reports_export_to_excel_pdf_and_csv(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon(self::FEATURES);
        $this->seedFinancials($tenant, $owner, $branch);

        $month = ['from' => now()->startOfMonth()->toDateString(), 'to' => now()->endOfMonth()->toDateString()];
        $xlsx = $this->actingAs($owner)->get(route('reports.profit-loss.export', $month + ['format' => 'xlsx']))->assertOk();
        $this->assertStringStartsWith('PK', $xlsx->getContent());
        $this->assertStringContainsString('spreadsheetml', $xlsx->headers->get('Content-Type'));

        $pdf = $this->actingAs($owner)->get(route('reports.profit-loss.export', $month + ['format' => 'pdf']))->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $csv = $this->actingAs($owner)->get(route('reports.profit-loss.export', $month + ['format' => 'csv']))->assertOk()->getContent();
        $this->assertStringContainsString(',"Net profit",2350.00', $csv);

        // Existing report pages now export too.
        foreach ([
            route('expense-management.reports.index', ['format' => 'xlsx']),
            route('commission-management.reports.index', ['format' => 'pdf']),
            route('payroll.reports.index', ['format' => 'csv']),
            route('loyalty-management.reports.index', ['format' => 'xlsx']),
            route('promotions.reports.index', ['format' => 'pdf']),
            route('billing.reports.sales.download', ['format' => 'xlsx']),
            route('billing.reports.tax.download', ['format' => 'pdf']),
            route('billing.reports.day-end', ['format' => 'csv']),
            route('branches.reports.show', ['branch' => $branch->id, 'format' => 'xlsx']),
            route('reports.branch-comparison.export', ['format' => 'xlsx']),
            route('reports.staff-performance.export', ['format' => 'pdf']),
        ] as $url) {
            $response = $this->actingAs($owner)->get($url);
            $response->assertOk();
            $this->assertStringContainsString('attachment;', (string) $response->headers->get('Content-Disposition'), $url);
        }

        $this->actingAs($owner)->get(route('reports.profit-loss.export', ['format' => 'docx']))->assertNotFound();
    }

    public function test_branch_comparison_and_staff_performance(): void
    {
        [$tenant, $owner, $colombo] = $this->makeSalon(self::FEATURES);
        $kandy = \App\Models\Branch::withoutTenantScope()->create([
            'tenant_id' => $tenant->id, 'name' => 'Kandy', 'code' => 'KDY-' . fake()->unique()->numberBetween(100, 999),
            'status' => \App\Models\Branch::STATUS_ACTIVE, 'is_active' => true, 'country' => 'LK', 'currency' => 'LKR', 'timezone' => 'Asia/Colombo',
        ]);
        $this->seedFinancials($tenant, $owner, $colombo);

        $staff = $this->makeStaff($tenant, $colombo);
        $customer = $this->makeCustomer($tenant, $colombo);
        $visit = $this->appointment($tenant, $colombo, $customer, $staff, now()->subDays(5), Appointment::STATUS_COMPLETED);
        $this->appointment($tenant, $colombo, $customer, $staff, now()->addDays(20), Appointment::STATUS_CONFIRMED);
        $other = $this->appointment($tenant, $colombo, $this->makeCustomer($tenant, $colombo), $staff, now()->subDays(4), Appointment::STATUS_COMPLETED);
        AppointmentFeedback::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'branch_id' => $colombo->id, 'appointment_id' => $visit->id, 'customer_id' => $customer->id, 'staff_id' => $staff->id, 'rating' => 5, 'submitted_at' => now()]);
        AppointmentFeedback::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'branch_id' => $colombo->id, 'appointment_id' => $other->id, 'staff_id' => $staff->id, 'rating' => 3, 'submitted_at' => now()]);

        $this->actingAs($owner)->get(route('reports.branch-comparison', ['from' => now()->subMonth()->toDateString(), 'to' => now()->addMonth()->toDateString()]))
            ->assertOk()->assertSee('Kandy')->assertSee($colombo->name)->assertSee('Most profitable');

        $response = $this->actingAs($owner)->get(route('reports.staff-performance', ['from' => now()->subMonth()->toDateString(), 'to' => now()->toDateString()]))->assertOk();
        $row = $response->viewData('report')['rows']->firstWhere(fn ($row) => $row['staff']->id === $staff->id);

        $this->assertSame(2, $row['completed']);
        $this->assertSame(1, $row['rebooked']);
        $this->assertEquals(50.0, $row['rebooking_rate']);
        $this->assertEquals(4.0, $row['rating']);
        $this->assertSame(2, $row['rating_count']);
        $this->assertNotNull($kandy);
    }

    public function test_reports_need_permission(): void
    {
        [$tenant] = $this->makeSalon(self::FEATURES);
        $beautician = \App\Models\User::factory()->create(['tenant_id' => $tenant->id, 'email_verified_at' => now()]);
        $beautician->assignRole('Beautician');

        $this->actingAs($beautician)->get(route('reports.profit-loss'))->assertForbidden();
        $this->actingAs($beautician)->get(route('reports.staff-performance'))->assertForbidden();
        $this->actingAs($beautician)->get(route('reports.schedules.index'))->assertForbidden();
    }

    public function test_scheduled_reports_are_emailed_when_due(): void
    {
        Notification::fake();
        [$tenant, $owner, $branch] = $this->makeSalon(self::FEATURES);
        $this->seedFinancials($tenant, $owner, $branch);

        $this->actingAs($owner)->post(route('reports.schedules.store'), [
            'name' => 'Weekly P&L',
            'report' => 'profit_loss',
            'frequency' => 'weekly',
            'day_of_week' => 1,
            'send_time' => '08:00',
            'format' => 'pdf',
            'recipients_text' => 'owner@salon.test, accountant@salon.test',
        ])->assertRedirect()->assertSessionHas('status');

        $schedule = ReportSchedule::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertSame(['owner@salon.test', 'accountant@salon.test'], $schedule->recipients);
        $this->assertSame(1, $schedule->next_run_at->dayOfWeekIso);
        $this->assertSame('08:00', $schedule->next_run_at->format('H:i'));

        // Not yet due: nothing is sent.
        $this->artisan('reports:send-scheduled', ['--tenant_id' => $tenant->id])->assertSuccessful();
        Notification::assertNothingSent();

        $schedule->update(['next_run_at' => CarbonImmutable::now('Asia/Colombo')->subMinute()->toDateTimeString()]);
        $this->artisan('reports:send-scheduled', ['--tenant_id' => $tenant->id])->assertSuccessful();

        Notification::assertSentOnDemand(ScheduledReportNotification::class, function (ScheduledReportNotification $notification, $channels, $notifiable) {
            return $notifiable->routes['mail'] === ['owner@salon.test', 'accountant@salon.test']
                && str_starts_with(base64_decode($notification->content), '%PDF')
                && $notification->reportTitle === 'Profit & Loss';
        });

        $schedule->refresh();
        $this->assertNotNull($schedule->last_sent_at);
        $this->assertTrue($schedule->next_run_at->isFuture() || $schedule->next_run_at->gt(now()->subHours(6)));

        // Monthly schedules clamp to short months; invalid emails are refused.
        $this->actingAs($owner)->post(route('reports.schedules.store'), [
            'name' => 'Broken', 'report' => 'sales', 'frequency' => 'daily', 'send_time' => '07:00', 'format' => 'xlsx', 'recipients_text' => 'not-an-email',
        ])->assertSessionHasErrors();

        $this->actingAs($owner)->post(route('reports.schedules.send', $schedule))->assertRedirect();
        $this->actingAs($owner)->post(route('reports.schedules.toggle', $schedule))->assertRedirect();
        $this->assertFalse($schedule->fresh()->is_active);
        $this->actingAs($owner)->get(route('reports.schedules.index'))->assertOk()->assertSee('Weekly P&L')->assertSee('Paused');
    }

    private function seedFinancials($tenant, $owner, $branch): void
    {
        $customer = $this->makeCustomer($tenant, $branch);
        $product = $this->makeProduct($tenant);

        $invoice = Invoice::withoutTenantScope()->create([
            'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'customer_id' => $customer->id,
            'invoice_number' => 'INV-' . fake()->unique()->numberBetween(100000, 999999),
            'subtotal' => 12000, 'total' => 11165, 'paid_amount' => 11165, 'status' => Invoice::STATUS_ISSUED,
            'payment_status' => Invoice::PAYMENT_PAID, 'issued_at' => now(), 'paid_at' => now(),
        ]);
        foreach ([['service', 10000, 1000, 150, null], ['product', 2000, 0, 15, $product->id]] as [$type, $gross, $discount, $tax, $productId]) {
            InvoiceItem::withoutTenantScope()->create([
                'tenant_id' => $tenant->id, 'sale_id' => $invoice->id, 'item_type' => $type, 'item_id' => $productId ?? 0, 'product_id' => $productId,
                'item_name' => ucfirst($type), 'quantity' => 1, 'unit_price' => $gross, 'gross_amount' => $gross, 'discount_amount' => $discount,
                'tax_amount' => $tax, 'net_amount' => $gross - $discount + $tax, 'total_amount' => $gross - $discount + $tax, 'total' => $gross - $discount + $tax,
            ]);
        }

        Refund::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'sale_id' => $invoice->id, 'amount' => 500, 'status' => Refund::STATUS_COMPLETED, 'processed_at' => now()]);

        StockMovement::withoutTenantScope()->create([
            'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'product_id' => $product->id, 'type' => StockMovement::TYPE_POS_SALE,
            'quantity' => -2, 'quantity_before' => 10, 'quantity_after' => 8, 'unit_cost' => 400, 'total_cost' => 800,
        ]);

        $category = ExpenseCategory::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'name' => 'Rent']);
        Expense::withoutTenantScope()->create([
            'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'category_id' => $category->id, 'description' => 'Shop rent',
            'amount' => 1500, 'total_amount' => 1500, 'expense_date' => now()->toDateString(), 'expense_status' => Expense::STATUS_CONFIRMED,
        ]);
        Expense::withoutTenantScope()->create([
            'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'category_id' => $category->id, 'description' => 'Cancelled',
            'amount' => 999, 'total_amount' => 999, 'expense_date' => now()->toDateString(), 'expense_status' => Expense::STATUS_CANCELLED,
        ]);

        $staff = $this->makeStaff($tenant, $branch);
        $period = PayrollPeriod::withoutTenantScope()->create([
            'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'name' => 'This month',
            'start_date' => now()->startOfMonth()->toDateString(), 'end_date' => now()->endOfMonth()->toDateString(), 'status' => 'approved',
        ]);
        $run = PayrollRun::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'payroll_period_id' => $period->id, 'status' => PayrollRun::STATUS_APPROVED]);
        PayrollItem::withoutTenantScope()->create([
            'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'payroll_period_id' => $period->id, 'payroll_run_id' => $run->id, 'staff_id' => $staff->id,
            'gross_pay' => 5000, 'tip_amount' => 200, 'epf_employer_amount' => 600, 'etf_employer_amount' => 150, 'net_pay' => 4600, 'status' => PayrollItem::STATUS_APPROVED,
        ]);

        CommissionPayout::withoutTenantScope()->create([
            'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'staff_id' => $staff->id, 'payout_number' => 'CP-' . fake()->unique()->numberBetween(1000, 9999),
            'period_start' => now()->startOfMonth()->toDateString(), 'period_end' => now()->toDateString(),
            'gross_commission' => 300, 'net_payable' => 300, 'status' => CommissionPayout::STATUS_PAID, 'paid_at' => now(),
        ]);
    }

    private function appointment($tenant, $branch, $customer, $staff, $startsAt, string $status): Appointment
    {
        return Appointment::withoutTenantScope()->create([
            'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'customer_id' => $customer->id, 'staff_id' => $staff->id,
            'appointment_number' => 'APT-' . fake()->unique()->numberBetween(100000, 999999),
            'starts_at' => $startsAt, 'ends_at' => $startsAt->copy()->addHour(), 'status' => $status,
        ]);
    }
}
