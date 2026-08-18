<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ExpenseManagementTest extends TestCase
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
            ->givePermissionTo(['expenses.view', 'expenses.create', 'expense_categories.view', 'vendors.view']);
    }

    public function test_owner_can_create_expense_with_payment_and_receipt(): void
    {
        Storage::fake('public');
        [$tenant, $owner, $branch] = $this->tenantSetup();
        [$category, $vendor, $method] = $this->expenseSetup($tenant);

        $this->actingAs($owner)
            ->post(route('expense-management.expenses.store'), [
                'branch_id' => $branch->id,
                'category_id' => $category->id,
                'vendor_id' => $vendor->id,
                'expense_date' => '2026-08-17',
                'description' => 'July electricity bill',
                'reference_number' => 'CEB-458744',
                'subtotal' => 18500,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'payment_method_id' => $method->id,
                'paid_amount' => 18500,
                'payment_date' => '2026-08-17',
                'payment_reference_number' => 'TRX923858',
                'receipt' => UploadedFile::fake()->create('electricity.pdf', 120, 'application/pdf'),
            ])
            ->assertRedirect();

        $expense = Expense::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->assertStringStartsWith('EXP-2026-', $expense->expense_number);
        $this->assertSame(Expense::PAYMENT_PAID, $expense->payment_status);
        $this->assertSame(Expense::APPROVAL_PENDING, $expense->approval_status);
        $this->assertEquals('18500.00', $expense->total_amount);
        $this->assertEquals('0.00', $expense->balance_amount);

        $this->assertDatabaseHas('expense_payments', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'expense_id' => $expense->id,
            'payment_method_id' => $method->id,
            'amount' => 18500,
            'reference_number' => 'TRX923858',
        ]);

        $this->assertDatabaseHas('expense_attachments', [
            'tenant_id' => $tenant->id,
            'expense_id' => $expense->id,
            'file_name' => 'electricity.pdf',
            'uploaded_by' => $owner->id,
        ]);
    }

    public function test_expense_can_be_partially_paid_approved_and_cancelled(): void
    {
        [$tenant, $owner, $branch] = $this->tenantSetup();
        [$category, $vendor, $method] = $this->expenseSetup($tenant);
        $expense = $this->expense($tenant, $branch, $category, $vendor, 50000);

        $this->actingAs($owner)
            ->post(route('expense-management.expenses.payments.store', $expense), [
                'payment_method_id' => $method->id,
                'amount' => 20000,
                'payment_date' => '2026-08-17',
            ])
            ->assertRedirect();

        $expense->refresh();
        $this->assertSame(Expense::PAYMENT_PARTIAL, $expense->payment_status);
        $this->assertEquals('20000.00', $expense->paid_amount);
        $this->assertEquals('30000.00', $expense->balance_amount);

        $this->actingAs($owner)
            ->post(route('expense-management.expenses.approve', $expense))
            ->assertRedirect();

        $expense->refresh();
        $this->assertSame(Expense::APPROVAL_APPROVED, $expense->approval_status);
        $this->assertSame($owner->id, $expense->approved_by);

        $this->actingAs($owner)
            ->post(route('expense-management.expenses.cancel', $expense), ['cancel_reason' => 'Entered twice'])
            ->assertRedirect();

        $expense->refresh();
        $this->assertSame(Expense::STATUS_CANCELLED, $expense->expense_status);
        $this->assertSame($owner->id, $expense->cancelled_by);
    }

    public function test_expense_pages_are_tenant_isolated(): void
    {
        [$tenant, $owner, $branch] = $this->tenantSetup();
        [$category, $vendor] = $this->expenseSetup($tenant);
        $ownExpense = $this->expense($tenant, $branch, $category, $vendor, 10000);

        [$otherTenant, , $otherBranch] = $this->tenantSetup();
        [$otherCategory, $otherVendor] = $this->expenseSetup($otherTenant);
        $otherExpense = $this->expense($otherTenant, $otherBranch, $otherCategory, $otherVendor, 9000);

        $this->actingAs($owner)
            ->get(route('expense-management.expenses.index'))
            ->assertOk()
            ->assertSee($ownExpense->expense_number)
            ->assertDontSee($otherExpense->expense_number);
    }

    public function test_branch_limited_user_cannot_create_expense_for_unassigned_branch(): void
    {
        [$tenant, , $branch] = $this->tenantSetup();
        $otherBranch = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Kandy',
            'code' => 'KDY-' . fake()->unique()->numberBetween(1000, 9999),
            'status' => Branch::STATUS_ACTIVE,
            'is_active' => true,
            'country' => 'LK',
            'currency' => 'LKR',
            'timezone' => 'Asia/Colombo',
        ]);
        [$category, $vendor] = $this->expenseSetup($tenant);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('Receptionist');
        $branch->users()->attach($user->id, ['tenant_id' => $tenant->id]);

        $this->actingAs($user)
            ->post(route('expense-management.expenses.store'), [
                'branch_id' => $otherBranch->id,
                'category_id' => $category->id,
                'vendor_id' => $vendor->id,
                'expense_date' => '2026-08-17',
                'description' => 'Cleaning supplies',
                'subtotal' => 2500,
            ])
            ->assertForbidden();
    }

    public function test_expense_dashboard_and_reports_are_available(): void
    {
        [$tenant, $owner, $branch] = $this->tenantSetup();
        [$category, $vendor] = $this->expenseSetup($tenant);
        $this->expense($tenant, $branch, $category, $vendor, 12500);

        $this->actingAs($owner)->get(route('expense-management.dashboard'))->assertOk()->assertSee('Recent Expenses');
        $this->actingAs($owner)->get(route('expense-management.reports.index'))->assertOk()->assertSee('Expense Reports');
        $this->actingAs($owner)->get(route('expense-management.categories.index'))->assertOk()->assertSee($category->name);
        $this->actingAs($owner)->get(route('expense-management.vendors.index'))->assertOk()->assertSee($vendor->name);
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
                'expenses' => true,
                'pos_billing' => true,
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

    private function expenseSetup(Tenant $tenant): array
    {
        $category = ExpenseCategory::create([
            'tenant_id' => $tenant->id,
            'name' => 'Utilities ' . fake()->unique()->numberBetween(100, 999),
            'code' => 'UTIL-' . fake()->unique()->numberBetween(100, 999),
            'is_active' => true,
        ]);

        $vendor = Vendor::create([
            'tenant_id' => $tenant->id,
            'name' => 'Ceylon Electricity Board ' . fake()->unique()->numberBetween(100, 999),
            'is_active' => true,
        ]);

        $method = PaymentMethod::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cash',
            'code' => 'cash-' . fake()->unique()->numberBetween(100, 999),
            'type' => PaymentMethod::TYPE_CASH,
            'is_active' => true,
            'requires_reference' => false,
        ]);

        return [$category, $vendor, $method];
    }

    private function expense(Tenant $tenant, Branch $branch, ExpenseCategory $category, Vendor $vendor, int $amount): Expense
    {
        $expense = Expense::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'expense_number' => 'EXP-2026-' . fake()->unique()->numberBetween(100000, 999999),
            'category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'expense_date' => '2026-08-17',
            'description' => 'Utility bill',
            'amount' => $amount,
            'subtotal' => $amount,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => $amount,
            'paid_amount' => 0,
            'balance_amount' => $amount,
            'currency' => 'LKR',
            'payment_status' => Expense::PAYMENT_UNPAID,
            'approval_status' => Expense::APPROVAL_PENDING,
            'expense_status' => Expense::STATUS_CONFIRMED,
        ]);

        return $expense->fresh();
    }
}
