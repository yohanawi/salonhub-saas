<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\PayrollItem;
use App\Models\PayrollItemLine;
use App\Models\PayrollPayment;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\SalaryAdvance;
use App\Models\Staff;
use App\Models\StaffCommission;
use App\Models\StaffSalaryStructure;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Payroll\SalaryStructureService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PayrollManagementTest extends TestCase
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

        Role::firstOrCreate(['name' => 'Beautician', 'guard_name' => 'web'])
            ->givePermissionTo(['payslips.view_own']);
    }

    public function test_owner_can_generate_payroll_with_salary_and_approved_commission(): void
    {
        [$tenant, $owner, $branch, $staff] = $this->tenantSetup();
        $this->salaryStructure($tenant, $owner, $branch, $staff, 80000);
        $this->approvedCommission($tenant, $owner, $branch, $staff, 12500);
        $period = $this->period($tenant, $branch, $owner);

        $this->actingAs($owner)
            ->post(route('payroll.periods.generate', $period))
            ->assertRedirect();

        $run = PayrollRun::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();
        $item = PayrollItem::withoutTenantScope()->where('payroll_run_id', $run->id)->firstOrFail();

        $this->assertStringStartsWith('PAY-2026-', $run->run_number);
        $this->assertSame(PayrollRun::STATUS_CALCULATED, $run->status);
        $this->assertSame(1, $run->employees_count);
        $this->assertEquals('80000.00', $run->basic_salary_total);
        $this->assertEquals('12500.00', $run->commission_total);
        $this->assertEquals('92500.00', $run->gross_pay);
        $this->assertEquals('92500.00', $run->net_pay);
        $this->assertEquals('80000.00', $item->basic_pay);
        $this->assertEquals('12500.00', $item->commission_amount);

        $this->assertDatabaseHas('payroll_item_lines', [
            'tenant_id' => $tenant->id,
            'payroll_item_id' => $item->id,
            'category' => 'basic_salary',
            'amount' => 80000,
        ]);
        $this->assertDatabaseHas('payroll_item_lines', [
            'tenant_id' => $tenant->id,
            'payroll_item_id' => $item->id,
            'category' => 'staff_commission',
            'amount' => 12500,
        ]);
    }

    public function test_approved_payroll_can_be_paid_and_marks_imported_commission_paid(): void
    {
        [$tenant, $owner, $branch, $staff] = $this->tenantSetup();
        $this->salaryStructure($tenant, $owner, $branch, $staff, 80000);
        $commission = $this->approvedCommission($tenant, $owner, $branch, $staff, 12500);
        $period = $this->period($tenant, $branch, $owner);

        $this->actingAs($owner)->post(route('payroll.periods.generate', $period))->assertRedirect();
        $run = PayrollRun::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->actingAs($owner)->post(route('payroll.runs.approve', $run))->assertRedirect();
        $this->actingAs($owner)
            ->post(route('payroll.runs.payments.store', $run), [
                'payment_method' => 'bank_transfer',
                'payment_reference' => 'BNK-2026-0001',
                'payment_date' => '2026-08-31',
            ])
            ->assertRedirect();

        $run->refresh();
        $item = PayrollItem::withoutTenantScope()->where('payroll_run_id', $run->id)->firstOrFail();

        $this->assertSame(PayrollRun::STATUS_PAID, $run->status);
        $this->assertSame(PayrollRun::PAYMENT_PAID, $run->payment_status);
        $this->assertSame(PayrollItem::STATUS_PAID, $item->status);
        $this->assertEquals('92500.00', $item->paid_amount);
        $this->assertEquals('0.00', $item->balance_amount);
        $this->assertSame(StaffCommission::STATUS_PAID, $commission->fresh()->status);
        $this->assertSame(1, PayrollPayment::withoutTenantScope()->where('tenant_id', $tenant->id)->count());
    }

    public function test_paid_salary_advance_is_deducted_and_recovered_through_payroll(): void
    {
        [$tenant, $owner, $branch, $staff] = $this->tenantSetup();
        $this->salaryStructure($tenant, $owner, $branch, $staff, 80000);
        $advance = $this->paidAdvance($tenant, $owner, $branch, $staff, 30000, 10000);
        $period = $this->period($tenant, $branch, $owner);

        $this->actingAs($owner)->post(route('payroll.periods.generate', $period))->assertRedirect();
        $run = PayrollRun::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();
        $item = PayrollItem::withoutTenantScope()->where('payroll_run_id', $run->id)->firstOrFail();

        $this->assertEquals('10000.00', $item->advance_deduction);
        $this->assertEquals('10000.00', $item->total_deductions);
        $this->assertEquals('70000.00', $item->net_pay);
        $this->assertDatabaseHas('payroll_item_lines', [
            'tenant_id' => $tenant->id,
            'payroll_item_id' => $item->id,
            'line_type' => PayrollItemLine::TYPE_DEDUCTION,
            'category' => 'salary_advance',
            'amount' => 10000,
        ]);

        $this->actingAs($owner)->post(route('payroll.runs.approve', $run))->assertRedirect();
        $this->actingAs($owner)
            ->post(route('payroll.runs.payments.store', $run), [
                'payment_method' => 'cash',
                'payment_date' => '2026-08-31',
            ])
            ->assertRedirect();

        $advance->refresh();
        $this->assertSame(SalaryAdvance::STATUS_PAID, $advance->status);
        $this->assertEquals('10000.00', $advance->recovered_amount);
        $this->assertEquals('20000.00', $advance->outstanding_amount);
    }

    public function test_salary_structure_snapshot_protects_historical_payroll(): void
    {
        [$tenant, $owner, $branch, $staff] = $this->tenantSetup();
        $this->salaryStructure($tenant, $owner, $branch, $staff, 80000);
        $period = $this->period($tenant, $branch, $owner);

        $this->actingAs($owner)->post(route('payroll.periods.generate', $period))->assertRedirect();

        app(SalaryStructureService::class)->create($tenant, [
            'branch_id' => $branch->id,
            'staff_id' => $staff->id,
            'salary_type' => StaffSalaryStructure::TYPE_SALARY_COMMISSION,
            'basic_salary' => 90000,
            'commission_enabled' => true,
            'payroll_frequency' => 'monthly',
            'effective_from' => '2026-09-01',
            'status' => StaffSalaryStructure::STATUS_ACTIVE,
        ], $owner);

        $item = PayrollItem::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->assertEquals('80000.00', $item->basic_salary_snapshot);
        $this->assertEquals('80000.00', $item->basic_pay);
    }

    public function test_payroll_pages_are_tenant_isolated(): void
    {
        [$tenant, $owner, $branch, $staff] = $this->tenantSetup();
        $this->salaryStructure($tenant, $owner, $branch, $staff, 80000);
        $period = $this->period($tenant, $branch, $owner);
        $this->actingAs($owner)->post(route('payroll.periods.generate', $period))->assertRedirect();
        $ownRun = PayrollRun::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();

        [$otherTenant, $otherOwner, $otherBranch, $otherStaff] = $this->tenantSetup();
        $this->salaryStructure($otherTenant, $otherOwner, $otherBranch, $otherStaff, 70000);
        $otherPeriod = $this->period($otherTenant, $otherBranch, $otherOwner);
        $this->actingAs($otherOwner)->post(route('payroll.periods.generate', $otherPeriod))->assertRedirect();
        $otherRun = PayrollRun::withoutTenantScope()->where('tenant_id', $otherTenant->id)->firstOrFail();

        $this->actingAs($owner)
            ->get(route('payroll.runs.index'))
            ->assertOk()
            ->assertSee($ownRun->run_number)
            ->assertDontSee($otherRun->run_number);
    }

    public function test_staff_member_can_only_view_own_payslip(): void
    {
        [$tenant, $owner, $branch, $staff] = $this->tenantSetup();
        $this->salaryStructure($tenant, $owner, $branch, $staff, 80000);
        $period = $this->period($tenant, $branch, $owner);
        $this->actingAs($owner)->post(route('payroll.periods.generate', $period))->assertRedirect();
        $item = PayrollItem::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();

        $staffUser = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);
        $staffUser->assignRole('Beautician');
        $staff->update(['user_id' => $staffUser->id]);

        $this->actingAs($staffUser)
            ->get(route('payroll.payslips.show', $item))
            ->assertOk()
            ->assertSee($staff->full_name);

        [$otherTenant, $otherOwner, $otherBranch, $otherStaff] = $this->tenantSetup();
        $this->salaryStructure($otherTenant, $otherOwner, $otherBranch, $otherStaff, 70000);
        $otherPeriod = $this->period($otherTenant, $otherBranch, $otherOwner);
        $this->actingAs($otherOwner)->post(route('payroll.periods.generate', $otherPeriod))->assertRedirect();
        $otherItem = PayrollItem::withoutTenantScope()->where('tenant_id', $otherTenant->id)->firstOrFail();

        $this->actingAs($staffUser)
            ->get(route('payroll.payslips.show', $otherItem))
            ->assertForbidden();
    }

    public function test_payroll_management_pages_are_available(): void
    {
        [$tenant, $owner, $branch, $staff] = $this->tenantSetup();
        $this->salaryStructure($tenant, $owner, $branch, $staff, 80000);
        $period = $this->period($tenant, $branch, $owner);
        $this->actingAs($owner)->post(route('payroll.periods.generate', $period))->assertRedirect();
        $run = PayrollRun::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->actingAs($owner)->get(route('payroll.dashboard'))->assertOk()->assertSee('Payroll Dashboard');
        $this->actingAs($owner)->get(route('payroll.salary-structures.index'))->assertOk()->assertSee('Salary Structures');
        $this->actingAs($owner)->get(route('payroll.periods.index'))->assertOk()->assertSee('Payroll Periods');
        $this->actingAs($owner)->get(route('payroll.periods.show', $period))->assertOk()->assertSee($period->name);
        $this->actingAs($owner)->get(route('payroll.runs.index'))->assertOk()->assertSee($run->run_number);
        $this->actingAs($owner)->get(route('payroll.runs.show', $run))->assertOk()->assertSee('Payroll Run');
        $this->actingAs($owner)->get(route('payroll.reports.index'))->assertOk()->assertSee('Payroll Reports');
    }

    public function test_regenerating_payroll_replaces_previous_run_without_double_counting(): void
    {
        [$tenant, $owner, $branch, $staff] = $this->tenantSetup();
        $this->salaryStructure($tenant, $owner, $branch, $staff, 80000);
        $this->approvedCommission($tenant, $owner, $branch, $staff, 12500);
        $period = $this->period($tenant, $branch, $owner);

        $this->actingAs($owner)->post(route('payroll.periods.generate', $period))->assertRedirect();
        $this->actingAs($owner)->post(route('payroll.periods.generate', $period))->assertRedirect();

        $runs = PayrollRun::withoutTenantScope()->where('payroll_period_id', $period->id)->orderBy('id')->get();

        $this->assertCount(2, $runs);
        $this->assertSame(PayrollRun::STATUS_CANCELLED, $runs[0]->status);
        $this->assertSame(PayrollRun::STATUS_CALCULATED, $runs[1]->status);
        $this->assertEquals('12500.00', $runs[1]->commission_total);
    }

    public function test_approved_payroll_period_cannot_be_regenerated(): void
    {
        [$tenant, $owner, $branch, $staff] = $this->tenantSetup();
        $this->salaryStructure($tenant, $owner, $branch, $staff, 80000);
        $period = $this->period($tenant, $branch, $owner);

        $this->actingAs($owner)->post(route('payroll.periods.generate', $period))->assertRedirect();
        $run = PayrollRun::withoutTenantScope()->where('payroll_period_id', $period->id)->firstOrFail();
        $this->actingAs($owner)->post(route('payroll.runs.approve', $run))->assertRedirect();

        $this->actingAs($owner)
            ->post(route('payroll.periods.generate', $period))
            ->assertSessionHasErrors('period');

        $this->assertSame(1, PayrollRun::withoutTenantScope()->where('payroll_period_id', $period->id)->count());
    }

    public function test_approved_payroll_cannot_be_paid_twice(): void
    {
        [$tenant, $owner, $branch, $staff] = $this->tenantSetup();
        $this->salaryStructure($tenant, $owner, $branch, $staff, 80000);
        $period = $this->period($tenant, $branch, $owner);

        $this->actingAs($owner)->post(route('payroll.periods.generate', $period))->assertRedirect();
        $run = PayrollRun::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->actingAs($owner)->post(route('payroll.runs.approve', $run))->assertRedirect();

        $payload = ['payment_method' => 'cash', 'payment_date' => '2026-08-31'];
        $this->actingAs($owner)->post(route('payroll.runs.payments.store', $run), $payload)->assertRedirect();
        $this->actingAs($owner)->post(route('payroll.runs.payments.store', $run), $payload);

        $this->assertSame(1, PayrollPayment::withoutTenantScope()->where('payroll_run_id', $run->id)->count());
    }

    public function test_commission_in_open_payout_is_not_imported_into_payroll_and_vice_versa(): void
    {
        [$tenant, $owner, $branch, $staff] = $this->tenantSetup();
        $this->salaryStructure($tenant, $owner, $branch, $staff, 80000);
        $commission = $this->approvedCommission($tenant, $owner, $branch, $staff, 12500);
        $period = $this->period($tenant, $branch, $owner);

        $payout = app(\App\Services\Commission\CommissionPayoutService::class)->create($tenant, $staff, [
            'branch_id' => $branch->id,
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
        ], $owner);

        $this->actingAs($owner)->post(route('payroll.periods.generate', $period))->assertRedirect();
        $run = PayrollRun::withoutTenantScope()->where('payroll_period_id', $period->id)->firstOrFail();
        $this->assertEquals('0.00', $run->commission_total);

        // Cancelling the payout releases the commission to the next payroll generation.
        app(\App\Services\Commission\CommissionPayoutService::class)->cancel($payout, $owner);
        $this->actingAs($owner)->post(route('payroll.periods.generate', $period))->assertRedirect();
        $run = PayrollRun::withoutTenantScope()->where('payroll_period_id', $period->id)->latest('id')->firstOrFail();
        $this->assertEquals('12500.00', $run->commission_total);

        // Now that payroll holds it, a new payout cannot claim the same commission.
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(\App\Services\Commission\CommissionPayoutService::class)->create($tenant, $staff, [
            'branch_id' => $branch->id,
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
        ], $owner);
    }

    public function test_payout_with_reversed_commission_cannot_be_paid_and_can_be_cancelled(): void
    {
        [$tenant, $owner, $branch, $staff] = $this->tenantSetup();
        $commission = $this->approvedCommission($tenant, $owner, $branch, $staff, 12500);

        $payout = app(\App\Services\Commission\CommissionPayoutService::class)->create($tenant, $staff, [
            'branch_id' => $branch->id,
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
        ], $owner);

        $this->actingAs($owner)->post(route('commission-management.payouts.approve', $payout))->assertRedirect();

        // The invoice behind the commission is voided after the payout was prepared.
        $commission->update(['status' => StaffCommission::STATUS_REVERSED]);

        $this->actingAs($owner)
            ->post(route('commission-management.payouts.pay', $payout), ['payment_method' => 'cash'])
            ->assertSessionHasErrors('payout');

        $this->assertSame(\App\Models\CommissionPayout::STATUS_APPROVED, $payout->fresh()->status);
        $this->assertSame(StaffCommission::STATUS_REVERSED, $commission->fresh()->status);

        $this->actingAs($owner)
            ->post(route('commission-management.payouts.cancel', $payout), ['reason' => 'Invoice voided'])
            ->assertRedirect();

        $this->assertSame(\App\Models\CommissionPayout::STATUS_CANCELLED, $payout->fresh()->status);
    }

    public function test_advance_recovery_never_exceeds_earnings_or_repeats_across_open_runs(): void
    {
        [$tenant, $owner, $branch, $staff] = $this->tenantSetup();
        $structure = $this->salaryStructure($tenant, $owner, $branch, $staff, 5000);
        $advance = $this->paidAdvance($tenant, $owner, $branch, $staff, 30000, 10000);
        $period = $this->period($tenant, $branch, $owner);

        $this->actingAs($owner)->post(route('payroll.periods.generate', $period))->assertRedirect();
        $item = PayrollItem::withoutTenantScope()->where('payroll_period_id', $period->id)->firstOrFail();

        // Only 5,000 was earned, so only 5,000 of the 10,000 installment can be withheld.
        $this->assertEquals('5000.00', $item->advance_deduction);
        $this->assertEquals('0.00', $item->net_pay);

        $run = PayrollRun::withoutTenantScope()->where('payroll_period_id', $period->id)->firstOrFail();
        $this->actingAs($owner)->post(route('payroll.runs.approve', $run))->assertRedirect();

        $september = PayrollPeriod::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'name' => 'September 2026 Payroll',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'status' => PayrollPeriod::STATUS_OPEN,
            'created_by' => $owner->id,
        ]);
        $structure->update(['basic_salary' => 80000]);
        $advance->update(['outstanding_amount' => 8000, 'installment_amount' => 10000]);

        $this->actingAs($owner)->post(route('payroll.periods.generate', $september))->assertRedirect();
        $septemberItem = PayrollItem::withoutTenantScope()->where('payroll_period_id', $september->id)->firstOrFail();

        // 5,000 is already scheduled in the unpaid August run, so only the remaining 3,000 is deducted.
        $this->assertEquals('3000.00', $septemberItem->advance_deduction);
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
                'payroll' => true,
                'staff_commissions' => true,
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

        $staff = Staff::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'STF-' . fake()->unique()->numberBetween(1000, 9999),
            'first_name' => 'Nadeesha',
            'last_name' => 'Perera',
            'job_title' => 'Senior Stylist',
            'status' => Staff::STATUS_ACTIVE,
            'is_bookable' => true,
        ]);

        $staff->branches()->attach($branch->id, [
            'tenant_id' => $tenant->id,
            'is_primary' => true,
            'status' => 'active',
        ]);

        return [$tenant, $owner, $branch, $staff];
    }

    private function salaryStructure(Tenant $tenant, User $owner, Branch $branch, Staff $staff, int $basicSalary): StaffSalaryStructure
    {
        return StaffSalaryStructure::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'staff_id' => $staff->id,
            'salary_type' => StaffSalaryStructure::TYPE_SALARY_COMMISSION,
            'basic_salary' => $basicSalary,
            'hourly_rate' => 0,
            'daily_rate' => 0,
            'commission_enabled' => true,
            'payroll_frequency' => 'monthly',
            'effective_from' => '2026-08-01',
            'status' => StaffSalaryStructure::STATUS_ACTIVE,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);
    }

    private function period(Tenant $tenant, Branch $branch, User $owner): PayrollPeriod
    {
        return PayrollPeriod::create([
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

    private function approvedCommission(Tenant $tenant, User $owner, Branch $branch, Staff $staff, int $amount): StaffCommission
    {
        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'invoice_number' => 'INV-' . fake()->unique()->numberBetween(100000, 999999),
            'subtotal' => 50000,
            'discount' => 0,
            'tax' => 0,
            'total' => 50000,
            'paid_amount' => 50000,
            'balance_amount' => 0,
            'status' => Invoice::STATUS_ISSUED,
            'payment_status' => Invoice::PAYMENT_PAID,
            'issued_at' => '2026-08-15 10:00:00',
            'paid_at' => '2026-08-15 10:05:00',
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        return StaffCommission::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'staff_id' => $staff->id,
            'sale_id' => $invoice->id,
            'invoice_id' => $invoice->id,
            'commission_type' => 'percentage',
            'commission_rate' => 25,
            'base_amount' => 50000,
            'gross_amount' => 50000,
            'discount_amount' => 0,
            'net_amount' => 50000,
            'commission_base' => 50000,
            'commission_amount' => $amount,
            'status' => StaffCommission::STATUS_APPROVED,
            'earned_at' => '2026-08-15 10:05:00',
            'approved_by' => $owner->id,
            'approved_at' => '2026-08-15 11:00:00',
        ]);
    }

    private function paidAdvance(Tenant $tenant, User $owner, Branch $branch, Staff $staff, int $amount, int $installment): SalaryAdvance
    {
        return SalaryAdvance::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'staff_id' => $staff->id,
            'advance_number' => 'ADV-' . fake()->unique()->numberBetween(100000, 999999),
            'requested_amount' => $amount,
            'approved_amount' => $amount,
            'paid_amount' => $amount,
            'recovered_amount' => 0,
            'outstanding_amount' => $amount,
            'repayment_type' => 'installment',
            'installment_amount' => $installment,
            'request_date' => '2026-08-01',
            'approved_date' => '2026-08-02',
            'paid_date' => '2026-08-03',
            'reason' => 'Emergency advance',
            'status' => SalaryAdvance::STATUS_PAID,
            'requested_by' => $owner->id,
            'approved_by' => $owner->id,
            'paid_by' => $owner->id,
        ]);
    }
}
