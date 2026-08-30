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
