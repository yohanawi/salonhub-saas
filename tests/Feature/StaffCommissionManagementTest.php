<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Branch;
use App\Models\CommissionPayout;
use App\Models\CommissionRule;
use App\Models\CommissionSetting;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffCommission;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Commission\CommissionGenerator;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StaffCommissionManagementTest extends TestCase
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
            ->givePermissionTo(['commissions.view_own']);
    }

    public function test_paid_invoice_generates_staff_service_commission_snapshot(): void
    {
        [$tenant, $owner, $appointment, $staff, $service] = $this->completedAppointmentSetup();

        CommissionRule::create([
            'tenant_id' => $tenant->id,
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'commission_scope' => CommissionRule::SCOPE_STAFF_SERVICE,
            'commission_type' => CommissionSetting::TYPE_PERCENTAGE,
            'commission_value' => 25,
            'calculate_on' => CommissionSetting::BASIS_NET_AFTER_DISCOUNT,
            'priority' => 10,
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $invoice = $this->checkout($tenant, $owner, $appointment);
        $commission = StaffCommission::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->assertSame($staff->id, $commission->staff_id);
        $this->assertSame($invoice->id, $commission->invoice_id);
        $this->assertSame($invoice->items()->first()->id, $commission->invoice_item_id);
        $this->assertSame(StaffCommission::STATUS_EARNED, $commission->status);
        $this->assertEquals('3000.00', $commission->gross_amount);
        $this->assertEquals('200.00', $commission->discount_amount);
        $this->assertEquals('2800.00', $commission->commission_base);
        $this->assertEquals('700.00', $commission->commission_amount);
    }

    public function test_commission_generation_is_idempotent_for_same_invoice_item(): void
    {
        [$tenant, $owner, $appointment] = $this->completedAppointmentSetup();
        $this->enableTenantDefaultCommission($tenant, 20);
        $invoice = $this->checkout($tenant, $owner, $appointment);

        app(CommissionGenerator::class)->generateForInvoice($invoice->fresh(['items.staff', 'items.service']), $owner);

        $this->assertSame(1, StaffCommission::withoutTenantScope()->where('tenant_id', $tenant->id)->count());
    }

    public function test_owner_can_approve_commission_and_pay_payout(): void
    {
        [$tenant, $owner, $appointment, $staff] = $this->completedAppointmentSetup();
        $this->enableTenantDefaultCommission($tenant, 20);
        $this->checkout($tenant, $owner, $appointment);
        $commission = StaffCommission::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->actingAs($owner)
            ->post(route('commission-management.ledger.approve', $commission))
            ->assertRedirect();

        $commission->refresh();
        $this->assertSame(StaffCommission::STATUS_APPROVED, $commission->status);

        $this->actingAs($owner)
            ->post(route('commission-management.payouts.store'), [
                'staff_id' => $staff->id,
                'period_start' => now()->subDay()->toDateString(),
                'period_end' => now()->addDay()->toDateString(),
                'adjustment_amount' => 100,
                'payment_method' => 'cash',
            ])
            ->assertRedirect();

        $payout = CommissionPayout::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertStringStartsWith('COM-', $payout->payout_number);
        $this->assertEquals('560.00', $payout->gross_commission);
        $this->assertEquals('660.00', $payout->net_payable);

        $this->actingAs($owner)->post(route('commission-management.payouts.approve', $payout))->assertRedirect();
        $this->actingAs($owner)
            ->post(route('commission-management.payouts.pay', $payout), [
                'payment_method' => 'cash',
                'payment_reference' => 'PAYROLL-001',
            ])
            ->assertRedirect();

        $commission->refresh();
        $payout->refresh();
        $this->assertSame(StaffCommission::STATUS_PAID, $commission->status);
        $this->assertSame(CommissionPayout::STATUS_PAID, $payout->status);
    }

    public function test_commission_pages_are_tenant_isolated(): void
    {
        [$tenant, $owner, $appointment] = $this->completedAppointmentSetup();
        $this->enableTenantDefaultCommission($tenant, 20);
        $ownInvoice = $this->checkout($tenant, $owner, $appointment);

        [$otherTenant, $otherOwner, $otherAppointment] = $this->completedAppointmentSetup();
        $this->enableTenantDefaultCommission($otherTenant, 20);
        $otherInvoice = $this->checkout($otherTenant, $otherOwner, $otherAppointment);

        $this->actingAs($owner)
            ->get(route('commission-management.ledger.index'))
            ->assertOk()
            ->assertSee($ownInvoice->invoice_number)
            ->assertDontSee($otherInvoice->invoice_number);
    }

    public function test_staff_member_can_only_view_own_commission(): void
    {
        [$tenant, $owner, $appointment, $staff] = $this->completedAppointmentSetup();
        $this->enableTenantDefaultCommission($tenant, 20);
        $this->checkout($tenant, $owner, $appointment);
        $commission = StaffCommission::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();

        $staffUser = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);
        $staffUser->assignRole('Beautician');
        $staff->update(['user_id' => $staffUser->id]);

        $this->actingAs($staffUser)
            ->get(route('commission-management.ledger.show', $commission))
            ->assertOk()
            ->assertSee('Commission #' . $commission->id);

        [$otherTenant, $otherOwner, $otherAppointment] = $this->completedAppointmentSetup();
        $this->enableTenantDefaultCommission($otherTenant, 20);
        $this->checkout($otherTenant, $otherOwner, $otherAppointment);
        $otherCommission = StaffCommission::withoutTenantScope()->where('tenant_id', $otherTenant->id)->firstOrFail();

        $this->actingAs($staffUser)
            ->get(route('commission-management.ledger.show', $otherCommission))
            ->assertForbidden();
    }

    public function test_management_pages_are_available(): void
    {
        [$tenant, $owner, $appointment] = $this->completedAppointmentSetup();
        $this->enableTenantDefaultCommission($tenant, 20);
        $this->checkout($tenant, $owner, $appointment);

        $this->actingAs($owner)->get(route('commission-management.dashboard'))->assertOk()->assertSee('Recent Commissions');
        $this->actingAs($owner)->get(route('commission-management.rules.index'))->assertOk()->assertSee('Commission Rules');
        $this->actingAs($owner)->get(route('commission-management.payouts.index'))->assertOk()->assertSee('Commission Payouts');
        $this->actingAs($owner)->get(route('commission-management.reports.index'))->assertOk()->assertSee('Commission Reports');
        $this->actingAs($owner)->get(route('commission-management.settings.edit'))->assertOk()->assertSee('Commission Settings');
    }

    private function checkout(Tenant $tenant, User $owner, Appointment $appointment): Invoice
    {
        $cash = PaymentMethod::withoutTenantScope()->firstOrCreate([
            'tenant_id' => $tenant->id,
            'code' => 'cash',
        ], [
            'name' => 'Cash',
            'type' => PaymentMethod::TYPE_CASH,
            'is_active' => true,
            'requires_reference' => false,
        ]);

        $this->actingAs($owner)
            ->post(route('billing.checkout.appointments.store', $appointment), [
                'payment_method_id' => $cash->id,
                'amount' => 2800,
            ])
            ->assertRedirect();

        return Invoice::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();
    }

    private function enableTenantDefaultCommission(Tenant $tenant, int $percentage): void
    {
        CommissionSetting::withoutTenantScope()->updateOrCreate([
            'tenant_id' => $tenant->id,
        ], [
            'commission_enabled' => true,
            'default_service_commission_type' => CommissionSetting::TYPE_PERCENTAGE,
            'default_service_commission_value' => $percentage,
            'default_product_commission_type' => CommissionSetting::TYPE_NONE,
            'default_product_commission_value' => 0,
            'calculation_basis' => CommissionSetting::BASIS_NET_AFTER_DISCOUNT,
            'earn_trigger' => CommissionSetting::TRIGGER_INVOICE_PAID,
            'requires_approval' => true,
            'allow_manual_adjustment' => false,
            'allow_negative_commission' => false,
            'refund_behavior' => 'reverse',
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

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'customer_code' => 'CUS-' . fake()->unique()->numberBetween(1000, 9999),
            'first_name' => 'Emma',
            'last_name' => 'Silva',
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
            'starts_at' => now()->setTime(10, 0),
            'ends_at' => now()->setTime(11, 0),
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
            'starts_at' => now()->setTime(10, 0),
            'ends_at' => now()->setTime(11, 0),
            'status' => Appointment::STATUS_COMPLETED,
        ]);

        return [$tenant, $owner, $appointment->fresh(['appointmentServices']), $staff, $service, $branch];
    }
}
