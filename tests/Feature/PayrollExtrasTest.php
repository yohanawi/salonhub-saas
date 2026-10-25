<?php

namespace Tests\Feature;

use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\SalaryAdvance;
use App\Models\Staff;
use App\Models\StaffSalaryStructure;
use App\Models\User;
use App\Notifications\PayslipNotification;
use App\Services\Payroll\PayslipService;
use App\Services\Settings\SettingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\Concerns\BuildsSalonFixtures;
use Tests\TestCase;

class PayrollExtrasTest extends TestCase
{
    use BuildsSalonFixtures;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_salary_advance_lifecycle_and_recovery_through_payroll(): void
    {
        [$tenant, $owner, $branch, $staff] = $this->payrollSalon();

        $this->actingAs($owner)->post(route('payroll.advances.store'), [
            'staff_id' => $staff->id,
            'amount' => 20000,
            'repayment_type' => 'installments',
            'installment_amount' => 5000,
            'reason' => 'Medical',
        ])->assertRedirect();

        $advance = SalaryAdvance::withoutTenantScope()->where('staff_id', $staff->id)->firstOrFail();
        $this->assertSame(SalaryAdvance::STATUS_REQUESTED, $advance->status);
        $this->assertStringStartsWith('ADV-', $advance->advance_number);

        // Cannot pay before approval; approve a lower amount, then pay out.
        $this->actingAs($owner)->post(route('payroll.advances.pay', $advance))->assertSessionHasErrors('status');
        $this->actingAs($owner)->post(route('payroll.advances.approve', $advance), ['approved_amount' => 15000])->assertRedirect();
        $this->actingAs($owner)->post(route('payroll.advances.pay', $advance), ['paid_date' => '2026-08-05'])->assertRedirect();

        $advance->refresh();
        $this->assertSame(SalaryAdvance::STATUS_PAID, $advance->status);
        $this->assertEquals('15000.00', $advance->outstanding_amount);

        $period = $this->period($tenant, $branch, $owner);
        $this->actingAs($owner)->post(route('payroll.periods.generate', $period))->assertRedirect();
        $item = PayrollItem::withoutTenantScope()->where('payroll_period_id', $period->id)->firstOrFail();

        // One installment is recovered in this payroll.
        $this->assertEquals('5000.00', $item->advance_deduction);
        $this->assertEquals('45000.00', $item->net_pay);

        $this->actingAs($owner)->get(route('payroll.advances.index'))->assertOk()->assertSee($advance->advance_number);
    }

    public function test_rejected_advance_and_permissions(): void
    {
        [$tenant, $owner, , $staff] = $this->payrollSalon();
        $beautician = User::factory()->create(['tenant_id' => $tenant->id, 'email_verified_at' => now()]);
        $beautician->assignRole('Beautician');

        $this->actingAs($owner)->post(route('payroll.advances.store'), ['staff_id' => $staff->id, 'amount' => 1000, 'repayment_type' => 'one_time']);
        $advance = SalaryAdvance::withoutTenantScope()->where('staff_id', $staff->id)->firstOrFail();

        $this->actingAs($beautician)->get(route('payroll.advances.index'))->assertForbidden();
        $this->actingAs($beautician)->post(route('payroll.advances.approve', $advance))->assertForbidden();

        $this->actingAs($owner)->post(route('payroll.advances.reject', $advance), ['rejection_reason' => 'Not eligible'])->assertRedirect();
        $this->assertSame(SalaryAdvance::STATUS_REJECTED, $advance->fresh()->status);
    }

    public function test_epf_and_etf_are_calculated_when_enabled(): void
    {
        [$tenant, $owner, $branch, $staff, $structure] = $this->payrollSalon();
        $structure->update(['epf_number' => 'EPF-778']);

        app(SettingService::class)->setMany($tenant, null, [
            'payroll.epf_enabled' => true,
            'payroll.employer_epf_number' => 'A/12345',
        ], $owner);

        $period = $this->period($tenant, $branch, $owner);
        $this->actingAs($owner)->post(route('payroll.periods.generate', $period))->assertRedirect();
        $item = PayrollItem::withoutTenantScope()->with('lines')->where('payroll_period_id', $period->id)->firstOrFail();

        $this->assertEquals('50000.00', $item->statutory_earnings);
        $this->assertEquals('4000.00', $item->epf_employee_amount);
        $this->assertEquals('6000.00', $item->epf_employer_amount);
        $this->assertEquals('1500.00', $item->etf_employer_amount);
        $this->assertEquals('46000.00', $item->net_pay);
        $this->assertTrue($item->lines->contains('category', 'epf_employee'));

        // Excluded staff pay nothing.
        $structure->update(['epf_applicable' => false]);
        $this->actingAs($owner)->post(route('payroll.periods.generate', $period))->assertRedirect();
        $regenerated = PayrollItem::withoutTenantScope()->where('payroll_period_id', $period->id)->where('status', '!=', PayrollItem::STATUS_CANCELLED)->firstOrFail();
        $this->assertEquals('0.00', $regenerated->epf_employee_amount);
        $this->assertEquals('50000.00', $regenerated->net_pay);

        // Report shows approved runs only.
        $structure->update(['epf_applicable' => true]);
        $this->actingAs($owner)->post(route('payroll.periods.generate', $period));
        $run = PayrollRun::withoutTenantScope()->where('payroll_period_id', $period->id)->latest('id')->firstOrFail();
        $this->actingAs($owner)->get(route('payroll.statutory.index', ['period_id' => $period->id]))->assertOk()->assertSee('No EPF / ETF contributions');
        $this->actingAs($owner)->post(route('payroll.runs.approve', $run))->assertRedirect();

        $this->actingAs($owner)->get(route('payroll.statutory.index', ['period_id' => $period->id]))
            ->assertOk()->assertSee('EPF-778')->assertSee('A/12345')->assertSee('10,000.00');

        $csv = $this->actingAs($owner)->get(route('payroll.statutory.export', ['period_id' => $period->id]))->assertOk()->streamedContent();
        $this->assertStringContainsString('EPF-778,50000.00,4000.00,6000.00,10000.00,1500.00', $csv);
    }

    public function test_payslip_pdf_delivery_and_bank_export(): void
    {
        Notification::fake();
        Queue::fake();
        [$tenant, $owner, $branch, $staff, $structure] = $this->payrollSalon();
        $staff->update(['email' => 'nadeesha@example.test', 'phone' => '0771234567']);
        $structure->update(['bank_name' => 'Commercial Bank', 'bank_code' => '7056', 'bank_branch_code' => '012', 'bank_account_number' => '8001234567', 'bank_account_name' => 'N Perera']);

        $this->enableSms($tenant, $owner);

        $period = $this->period($tenant, $branch, $owner);
        $this->actingAs($owner)->post(route('payroll.periods.generate', $period));
        $run = PayrollRun::withoutTenantScope()->where('payroll_period_id', $period->id)->firstOrFail();
        $item = PayrollItem::withoutTenantScope()->where('payroll_run_id', $run->id)->firstOrFail();

        // PDF download works at any stage for authorised users.
        $pdf = $this->actingAs($owner)->get(route('payroll.payslips.pdf', $item))->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        // Not sendable or exportable until approved.
        $this->actingAs($owner)->post(route('payroll.payslips.send', $item), ['channels' => ['email']])->assertSessionHasErrors('payslip');
        $this->actingAs($owner)->get(route('payroll.runs.bank-export', $run))->assertRedirect();

        $this->actingAs($owner)->post(route('payroll.runs.approve', $run))->assertRedirect();

        $this->actingAs($owner)->post(route('payroll.runs.send-payslips', $run), ['channels' => ['email', 'message']])->assertRedirect()->assertSessionHas('status');
        Notification::assertSentOnDemand(PayslipNotification::class, fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'nadeesha@example.test');
        $this->assertNotNull($item->fresh()->payslip_sent_at);
        $this->assertDatabaseHas('message_logs', ['tenant_id' => $tenant->id, 'event' => 'payslip']);

        // The emailed PDF builds correctly.
        $mail = (new PayslipNotification($item->fresh()))->toMail($staff);
        $this->assertCount(1, $mail->rawAttachments);

        // Signed public link opens the PDF without login; a tampered link does not.
        $url = app(PayslipService::class)->publicUrl($item);
        auth()->logout();
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('payslips.public', ['payslip' => $item->id]))->assertForbidden();

        $csv = $this->actingAs($owner)->get(route('payroll.runs.bank-export', $run))->assertOk()->streamedContent();
        $this->assertStringContainsString('"N Perera",8001234567,"Commercial Bank",7056,012,50000.00,' . $run->run_number, $csv);

        $this->actingAs($owner)->get(route('payroll.runs.show', $run))->assertOk()->assertSee('Bank Transfer File')->assertSee('Send All Payslips');
        $this->actingAs($owner)->get(route('payroll.payslips.show', $item))->assertOk()->assertSee('Download PDF');
    }

    public function test_overtime_from_attendance_is_paid_when_enabled(): void
    {
        [$tenant, $owner, $branch, $staff] = $this->payrollSalon();
        // Two 9-hour days on an 8-hour shift: 2 hours of overtime.
        foreach (['2026-08-03', '2026-08-04'] as $day) {
            $this->attendance($tenant, $branch, $staff, $day, 9 * 60);
        }

        $period = $this->period($tenant, $branch, $owner);
        $this->actingAs($owner)->post(route('payroll.periods.generate', $period));
        $item = PayrollItem::withoutTenantScope()->where('payroll_period_id', $period->id)->firstOrFail();
        $this->assertEquals('0.00', $item->overtime_amount); // off by default

        app(SettingService::class)->setMany($tenant, null, ['payroll.overtime_enabled' => true, 'payroll.overtime_rate' => 1.5], $owner);
        $this->actingAs($owner)->post(route('payroll.periods.generate', $period));
        $item = PayrollItem::withoutTenantScope()->with('lines')->where('payroll_period_id', $period->id)->where('status', '!=', PayrollItem::STATUS_CANCELLED)->latest('id')->firstOrFail();

        // 50,000 / 240 = 208.33 per hour × 1.5 × 2 hours.
        $this->assertEquals('625.00', $item->overtime_amount);
        $this->assertEquals('50625.00', $item->gross_pay);
        $this->assertTrue($item->lines->contains('category', 'overtime'));
    }

    public function test_hourly_staff_are_paid_for_clocked_hours(): void
    {
        [$tenant, $owner, $branch, $staff, $structure] = $this->payrollSalon();
        $structure->update(['salary_type' => StaffSalaryStructure::TYPE_HOURLY, 'basic_salary' => 0, 'hourly_rate' => 500]);
        $this->attendance($tenant, $branch, $staff, '2026-08-03', 8 * 60);
        $this->attendance($tenant, $branch, $staff, '2026-08-04', 6 * 60);

        $period = $this->period($tenant, $branch, $owner);
        $this->actingAs($owner)->post(route('payroll.periods.generate', $period));

        $item = PayrollItem::withoutTenantScope()->where('payroll_period_id', $period->id)->firstOrFail();
        $this->assertEquals('7000.00', $item->basic_pay); // 14 hours × 500
    }

    public function test_commission_can_be_kept_out_of_payroll_and_bank_file_names_the_debit_account(): void
    {
        [$tenant, $owner, $branch, $staff, $structure] = $this->payrollSalon();
        $structure->update(['commission_enabled' => true, 'bank_account_number' => '8001234567', 'bank_code' => '7056']);
        \App\Models\StaffCommission::withoutTenantScope()->create([
            'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'staff_id' => $staff->id,
            'commission_amount' => 2500, 'status' => \App\Models\StaffCommission::STATUS_APPROVED,
            'earned_at' => '2026-08-10 10:00:00',
        ]);
        app(SettingService::class)->setMany($tenant, null, [
            'payroll.commission_included' => false,
            'payroll.employer_bank_account' => '1000555777',
        ], $owner);

        $period = $this->period($tenant, $branch, $owner);
        $this->actingAs($owner)->post(route('payroll.periods.generate', $period));
        $item = PayrollItem::withoutTenantScope()->where('payroll_period_id', $period->id)->firstOrFail();
        $this->assertEquals('0.00', $item->commission_amount);
        $this->assertEquals('50000.00', $item->gross_pay);

        $run = PayrollRun::withoutTenantScope()->where('payroll_period_id', $period->id)->latest('id')->firstOrFail();
        $this->actingAs($owner)->post(route('payroll.runs.approve', $run));
        $csv = $this->actingAs($owner)->get(route('payroll.runs.bank-export', $run))->assertOk()->streamedContent();
        $this->assertStringContainsString('Debit Account', $csv);
        $this->assertStringContainsString('1000555777,', $csv);
    }

    private function attendance($tenant, $branch, Staff $staff, string $day, int $workedMinutes): void
    {
        \App\Models\StaffAttendance::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'staff_id' => $staff->id,
            'work_date' => $day,
            'clock_in_at' => "{$day} 09:00:00",
            'clock_out_at' => \Carbon\Carbon::parse("{$day} 09:00:00")->addMinutes($workedMinutes),
            'worked_minutes' => $workedMinutes,
            'scheduled_start' => '09:00',
            'scheduled_end' => '17:00',
            'status' => \App\Models\StaffAttendance::STATUS_PRESENT,
        ]);
    }

    /**
     * @return array{0: \App\Models\Tenant, 1: User, 2: \App\Models\Branch, 3: Staff, 4: StaffSalaryStructure}
     */
    private function payrollSalon(): array
    {
        [$tenant, $owner, $branch] = $this->makeSalon(['payroll']);
        $staff = $this->makeStaff($tenant, $branch);

        $structure = StaffSalaryStructure::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'staff_id' => $staff->id,
            'salary_type' => StaffSalaryStructure::TYPE_MONTHLY,
            'basic_salary' => 50000,
            'commission_enabled' => false,
            'payroll_frequency' => 'monthly',
            'effective_from' => '2026-08-01',
            'status' => StaffSalaryStructure::STATUS_ACTIVE,
        ]);

        return [$tenant, $owner, $branch, $staff, $structure];
    }

    private function period($tenant, $branch, $owner): PayrollPeriod
    {
        return PayrollPeriod::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'name' => 'August 2026 Payroll',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'pay_date' => '2026-08-31',
            'status' => PayrollPeriod::STATUS_OPEN,
            'created_by' => $owner->id,
        ]);
    }
}
