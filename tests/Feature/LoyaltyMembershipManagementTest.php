<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerLoyaltyAccount;
use App\Models\CustomerMembership;
use App\Models\Invoice;
use App\Models\LoyaltyEarningRule;
use App\Models\LoyaltyPointTransaction;
use App\Models\LoyaltyProgram;
use App\Models\MembershipBenefit;
use App\Models\MembershipBenefitUsage;
use App\Models\MembershipPlan;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Loyalty\LoyaltyService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LoyaltyMembershipManagementTest extends TestCase
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
            ->givePermissionTo(['loyalty.view', 'loyalty.redeem', 'memberships.view', 'memberships.create', 'membership_plans.view']);
    }

    public function test_paid_invoice_awards_loyalty_points_once(): void
    {
        [$tenant, $owner, $appointment] = $this->completedAppointmentSetup();
        $this->loyaltyProgram($tenant, $owner);

        $invoice = $this->checkout($tenant, $owner, $appointment, ['amount' => 2800]);

        $account = CustomerLoyaltyAccount::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertSame(28, $account->available_points);
        $this->assertSame(28, $invoice->fresh()->loyalty_points_earned);
        $this->assertSame(1, LoyaltyPointTransaction::withoutTenantScope()->where('tenant_id', $tenant->id)->where('type', LoyaltyPointTransaction::TYPE_EARN)->count());

        app(LoyaltyService::class)->earnFromInvoice($invoice->fresh(['tenant', 'customer', 'items']), $owner);
        $this->assertSame(1, LoyaltyPointTransaction::withoutTenantScope()->where('tenant_id', $tenant->id)->where('type', LoyaltyPointTransaction::TYPE_EARN)->count());
    }

    public function test_customer_can_redeem_points_during_checkout(): void
    {
        [$tenant, $owner, $appointment] = $this->completedAppointmentSetup();
        $program = $this->loyaltyProgram($tenant, $owner);
        $account = app(LoyaltyService::class)->accountFor($appointment->customer, $program);
        app(LoyaltyService::class)->addPoints($account, 500, LoyaltyPointTransaction::TYPE_BONUS, null, $owner, 'Opening balance');

        $invoice = $this->checkout($tenant, $owner, $appointment, [
            'amount' => 2800,
            'loyalty_points_to_redeem' => 100,
        ]);

        $invoice->refresh();
        $account->refresh();

        $this->assertEquals('500.00', $invoice->loyalty_redemption_amount);
        $this->assertSame(100, $invoice->loyalty_points_redeemed);
        $this->assertEquals('2300.00', $invoice->total);
        $this->assertDatabaseHas('loyalty_point_transactions', [
            'tenant_id' => $tenant->id,
            'type' => LoyaltyPointTransaction::TYPE_REDEEM,
            'points' => -100,
            'source_id' => $invoice->id,
        ]);
        $this->assertSame(423, $account->available_points);
    }

    public function test_membership_discount_applies_to_invoice_items_and_boosts_points(): void
    {
        [$tenant, $owner, $appointment, $service] = $this->completedAppointmentSetup();
        $this->loyaltyProgram($tenant, $owner);
        $this->activeMembership($tenant, $owner, $appointment->customer, $service);

        $invoice = $this->checkout($tenant, $owner, $appointment, ['amount' => 2800]);
        $item = $invoice->items()->firstOrFail();

        $this->assertEquals('280.00', $invoice->fresh()->membership_discount_amount);
        $this->assertEquals('280.00', $item->membership_discount_amount);
        $this->assertEquals('2520.00', $invoice->fresh()->total);
        $this->assertSame(50, $invoice->fresh()->loyalty_points_earned);
        $this->assertSame(1, MembershipBenefitUsage::withoutTenantScope()->where('tenant_id', $tenant->id)->count());
    }

    public function test_owner_can_purchase_membership_with_invoice_payment(): void
    {
        [$tenant, $owner, , , , $branch, $customer] = $this->completedAppointmentSetup();
        $plan = $this->membershipPlan($tenant, $owner);
        $cash = $this->cashMethod($tenant);

        $this->actingAs($owner)
            ->post(route('loyalty-management.memberships.store'), [
                'customer_id' => $customer->id,
                'membership_plan_id' => $plan->id,
                'branch_id' => $branch->id,
                'start_date' => '2026-08-17',
                'payment_method_id' => $cash->id,
            ])
            ->assertRedirect();

        $membership = CustomerMembership::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();
        $invoice = Invoice::withoutTenantScope()->where('tenant_id', $tenant->id)->where('customer_membership_id', $membership->id)->firstOrFail();

        $this->assertStringStartsWith('MEM-2026-', $membership->membership_number);
        $this->assertSame(CustomerMembership::STATUS_ACTIVE, $membership->status);
        $this->assertEquals('20000.00', $invoice->total);
        $this->assertSame(Invoice::PAYMENT_PAID, $invoice->payment_status);
        $this->assertSame(0, LoyaltyPointTransaction::withoutTenantScope()->where('tenant_id', $tenant->id)->count());
    }

    public function test_loyalty_pages_are_tenant_isolated(): void
    {
        [$tenant, $owner, $appointment] = $this->completedAppointmentSetup();
        $program = $this->loyaltyProgram($tenant, $owner);
        $account = app(LoyaltyService::class)->accountFor($appointment->customer, $program);
        app(LoyaltyService::class)->addPoints($account, 150, LoyaltyPointTransaction::TYPE_BONUS, null, $owner, 'Own adjustment');

        [$otherTenant, $otherOwner, $otherAppointment] = $this->completedAppointmentSetup();
        $otherProgram = $this->loyaltyProgram($otherTenant, $otherOwner);
        $otherAccount = app(LoyaltyService::class)->accountFor($otherAppointment->customer, $otherProgram);
        app(LoyaltyService::class)->addPoints($otherAccount, 999, LoyaltyPointTransaction::TYPE_BONUS, null, $otherOwner, 'Other adjustment');

        $this->actingAs($owner)
            ->get(route('loyalty-management.transactions.index'))
            ->assertOk()
            ->assertSee('Own adjustment')
            ->assertDontSee('Other adjustment');
    }

    public function test_loyalty_management_pages_are_available(): void
    {
        [$tenant, $owner, $appointment] = $this->completedAppointmentSetup();
        $program = $this->loyaltyProgram($tenant, $owner);
        $this->membershipPlan($tenant, $owner);
        $account = app(LoyaltyService::class)->accountFor($appointment->customer, $program);
        app(LoyaltyService::class)->addPoints($account, 150, LoyaltyPointTransaction::TYPE_BONUS, null, $owner, 'Page adjustment');

        $this->actingAs($owner)->get(route('loyalty-management.dashboard'))->assertOk()->assertSee('Recent Point Transactions');
        $this->actingAs($owner)->get(route('loyalty-management.programs.index'))->assertOk()->assertSee('Loyalty Programs');
        $this->actingAs($owner)->get(route('loyalty-management.rules.index'))->assertOk()->assertSee('Earning Rules');
        $this->actingAs($owner)->get(route('loyalty-management.transactions.index'))->assertOk()->assertSee('Page adjustment');
        $this->actingAs($owner)->get(route('loyalty-management.membership-plans.index'))->assertOk()->assertSee('Membership Plans');
        $this->actingAs($owner)->get(route('loyalty-management.memberships.index'))->assertOk()->assertSee('Customer Memberships');
        $this->actingAs($owner)->get(route('loyalty-management.reports.index'))->assertOk()->assertSee('Loyalty Reports');
    }

    private function checkout(Tenant $tenant, User $owner, Appointment $appointment, array $overrides = []): Invoice
    {
        $cash = $this->cashMethod($tenant);

        $this->actingAs($owner)
            ->post(route('billing.checkout.appointments.store', $appointment), array_merge([
                'payment_method_id' => $cash->id,
                'amount' => 2800,
            ], $overrides))
            ->assertRedirect();

        return Invoice::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('appointment_id', $appointment->id)
            ->firstOrFail();
    }

    private function loyaltyProgram(Tenant $tenant, User $owner): LoyaltyProgram
    {
        $program = LoyaltyProgram::create([
            'tenant_id' => $tenant->id,
            'name' => 'Glow Rewards',
            'status' => LoyaltyProgram::STATUS_ACTIVE,
            'points_expiry_days' => 365,
            'minimum_redeem_points' => 100,
            'maximum_redeem_percentage' => 30,
            'allow_partial_redemption' => true,
            'allow_points_on_discounted_sales' => false,
            'redemption_points' => 100,
            'redemption_value' => 500,
            'created_by' => $owner->id,
        ]);

        LoyaltyEarningRule::create([
            'tenant_id' => $tenant->id,
            'loyalty_program_id' => $program->id,
            'name' => 'Spend Points',
            'rule_type' => LoyaltyEarningRule::TYPE_SPEND,
            'spend_amount' => 100,
            'points_awarded' => 1,
            'status' => LoyaltyEarningRule::STATUS_ACTIVE,
        ]);

        return $program;
    }

    private function activeMembership(Tenant $tenant, User $owner, Customer $customer, Service $service): CustomerMembership
    {
        $plan = $this->membershipPlan($tenant, $owner);

        MembershipBenefit::create([
            'tenant_id' => $tenant->id,
            'membership_plan_id' => $plan->id,
            'benefit_type' => MembershipBenefit::TYPE_SERVICE_DISCOUNT,
            'discount_type' => MembershipBenefit::DISCOUNT_PERCENTAGE,
            'discount_value' => 10,
            'service_id' => $service->id,
            'priority' => 10,
            'status' => MembershipBenefit::STATUS_ACTIVE,
        ]);

        MembershipBenefit::create([
            'tenant_id' => $tenant->id,
            'membership_plan_id' => $plan->id,
            'benefit_type' => MembershipBenefit::TYPE_BONUS_POINTS_MULTIPLIER,
            'loyalty_multiplier' => 2,
            'priority' => 20,
            'status' => MembershipBenefit::STATUS_ACTIVE,
        ]);

        return CustomerMembership::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'membership_number' => 'MEM-2026-' . fake()->unique()->numberBetween(100000, 999999),
            'start_date' => '2026-08-01',
            'end_date' => '2027-07-31',
            'status' => CustomerMembership::STATUS_ACTIVE,
            'price_paid' => 20000,
            'created_by' => $owner->id,
        ]);
    }

    private function membershipPlan(Tenant $tenant, User $owner): MembershipPlan
    {
        return MembershipPlan::withoutTenantScope()->firstOrCreate([
            'tenant_id' => $tenant->id,
            'code' => 'gold',
        ], [
            'name' => 'Gold Membership',
            'description' => 'Annual premium membership',
            'price' => 20000,
            'duration_type' => 'months',
            'duration_value' => 12,
            'billing_type' => 'one_time',
            'joining_fee' => 0,
            'status' => MembershipPlan::STATUS_ACTIVE,
            'is_featured' => true,
            'created_by' => $owner->id,
        ]);
    }

    private function completedAppointmentSetup(): array
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
                'appointment_calendar' => true,
                'customer_management' => true,
                'services' => true,
                'pos_billing' => true,
                'loyalty_membership' => true,
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

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'customer_code' => 'CUS-' . fake()->unique()->numberBetween(1000, 9999),
            'first_name' => 'Nimali',
            'last_name' => 'Perera',
            'phone' => '0771234567',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $service = Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Hair Cut ' . fake()->unique()->numberBetween(100, 999),
            'slug' => 'hair-cut-' . fake()->unique()->numberBetween(100, 999),
            'price' => 3000,
            'duration_minutes' => 60,
            'default_price' => 3000,
            'default_duration_minutes' => 60,
            'is_active' => true,
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

        $appointment = Appointment::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'appointment_number' => 'APT-' . fake()->unique()->numberBetween(100000, 999999),
            'booking_source' => 'reception',
            'source' => 'reception',
            'status' => Appointment::STATUS_COMPLETED,
            'starts_at' => '2026-08-17 10:00',
            'ends_at' => '2026-08-17 11:00',
            'subtotal' => 3000,
            'discount_amount' => 200,
            'tax_amount' => 0,
            'total_amount' => 2800,
            'completed_at' => now(),
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        AppointmentService::create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $appointment->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'service_name' => 'Hair Cut',
            'duration_minutes' => 60,
            'price' => 3000,
            'unit_price' => 3000,
            'discount_amount' => 200,
            'total_price' => 2800,
            'starts_at' => '2026-08-17 10:00',
            'ends_at' => '2026-08-17 11:00',
            'status' => Appointment::STATUS_COMPLETED,
        ]);

        return [$tenant, $owner, $appointment->fresh(['customer', 'appointmentServices.service']), $service, $staff, $branch, $customer];
    }

    private function cashMethod(Tenant $tenant): PaymentMethod
    {
        return PaymentMethod::withoutTenantScope()->firstOrCreate([
            'tenant_id' => $tenant->id,
            'code' => 'cash',
        ], [
            'name' => 'Cash',
            'type' => PaymentMethod::TYPE_CASH,
            'is_active' => true,
            'requires_reference' => false,
        ]);
    }
}
