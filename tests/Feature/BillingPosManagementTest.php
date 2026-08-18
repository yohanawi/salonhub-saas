<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BillingPosManagementTest extends TestCase
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
            ->givePermissionTo(['appointments.view']);
    }

    public function test_owner_can_checkout_completed_appointment_to_invoice_and_payment(): void
    {
        [$tenant, $owner, $appointment] = $this->completedAppointmentSetup();

        $this->actingAs($owner)
            ->get(route('billing.checkout.appointments.create', $appointment))
            ->assertOk()
            ->assertSee('Checkout ' . $appointment->appointment_number);

        $cash = PaymentMethod::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('code', 'cash')
            ->firstOrFail();

        $this->actingAs($owner)
            ->post(route('billing.checkout.appointments.store', $appointment), [
                'payment_method_id' => $cash->id,
                'amount' => 2800,
                'cash_received' => 3000,
                'discount_amount' => 0,
                'tax_amount' => 0,
            ])
            ->assertRedirect();

        $invoice = Invoice::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->assertStringStartsWith('INV-', $invoice->invoice_number);
        $this->assertSame(Invoice::STATUS_ISSUED, $invoice->status);
        $this->assertSame(Invoice::PAYMENT_PAID, $invoice->payment_status);
        $this->assertEquals('3000.00', $invoice->subtotal);
        $this->assertEquals('200.00', $invoice->discount);
        $this->assertEquals('2800.00', $invoice->total);
        $this->assertEquals('2800.00', $invoice->paid_amount);
        $this->assertEquals('0.00', $invoice->balance_amount);

        $this->assertDatabaseHas('sale_items', [
            'tenant_id' => $tenant->id,
            'sale_id' => $invoice->id,
            'item_type' => 'service',
            'item_name' => 'Hair Cut',
            'unit_price' => 3000,
            'discount_amount' => 200,
            'total_amount' => 2800,
        ]);

        $this->assertDatabaseHas('payments', [
            'tenant_id' => $tenant->id,
            'sale_id' => $invoice->id,
            'payment_method_id' => $cash->id,
            'amount' => 2800,
            'cash_received' => 3000,
            'change_given' => 200,
            'status' => Payment::STATUS_COMPLETED,
            'received_by' => $owner->id,
        ]);
    }

    public function test_checkout_does_not_mutate_appointment_service_snapshot(): void
    {
        [$tenant, $owner, $appointment] = $this->completedAppointmentSetup();
        $appointmentService = $appointment->appointmentServices()->firstOrFail();
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

        $appointmentService->refresh();

        $this->assertEquals('3000.00', $appointmentService->unit_price);
        $this->assertEquals('200.00', $appointmentService->discount_amount);
        $this->assertEquals('2800.00', $appointmentService->total_price);
    }

    public function test_completed_appointment_cannot_generate_duplicate_invoice(): void
    {
        [$tenant, $owner, $appointment] = $this->completedAppointmentSetup();
        $cash = PaymentMethod::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Cash',
            'code' => 'cash',
            'type' => PaymentMethod::TYPE_CASH,
            'is_active' => true,
        ]);

        $payload = [
            'payment_method_id' => $cash->id,
            'amount' => 2800,
        ];

        $this->actingAs($owner)
            ->post(route('billing.checkout.appointments.store', $appointment), $payload)
            ->assertRedirect();

        $this->actingAs($owner)
            ->post(route('billing.checkout.appointments.store', $appointment), $payload)
            ->assertSessionHasErrors('appointment');

        $this->assertSame(1, Invoice::withoutTenantScope()->where('tenant_id', $tenant->id)->count());
    }

    public function test_billing_pages_are_available(): void
    {
        [$tenant, $owner, $appointment] = $this->completedAppointmentSetup();
        $cash = PaymentMethod::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Cash',
            'code' => 'cash',
            'type' => PaymentMethod::TYPE_CASH,
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('billing.checkout.appointments.store', $appointment), [
                'payment_method_id' => $cash->id,
                'amount' => 2800,
            ])
            ->assertRedirect();

        $invoice = Invoice::withoutTenantScope()->firstOrFail();

        $this->actingAs($owner)->get(route('billing.invoices.index'))->assertOk()->assertSee('Invoices');
        $this->actingAs($owner)->get(route('billing.invoices.show', $invoice))->assertOk()->assertSee($invoice->invoice_number);
        $this->actingAs($owner)->get(route('billing.invoices.receipt', $invoice))->assertOk()->assertSee('Thank you for visiting');
        $this->actingAs($owner)->get(route('billing.payments.index'))->assertOk()->assertSee('Payments');
        $this->actingAs($owner)->get(route('billing.payment-methods.index'))->assertOk()->assertSee('Payment Methods');
    }

    public function test_staff_without_billing_permission_cannot_checkout(): void
    {
        [$tenant, , $appointment] = $this->completedAppointmentSetup();
        $staffUser = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);
        $staffUser->assignRole('Beautician');

        $this->actingAs($staffUser)
            ->get(route('billing.checkout.appointments.create', $appointment))
            ->assertForbidden();
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
            'code' => 'CMB',
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
            'customer_code' => 'CUS-000001',
            'first_name' => 'Emma',
            'last_name' => 'Silva',
            'phone' => '0771234567',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $service = Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Hair Cut',
            'slug' => 'hair-cut',
            'price' => 3000,
            'duration_minutes' => 60,
            'default_price' => 3000,
            'default_duration_minutes' => 60,
            'is_active' => true,
        ]);

        $staff = Staff::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'STF-0001',
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
            'appointment_number' => 'APT-202608-000001',
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

        return [$tenant, $owner, $appointment->fresh(['appointmentServices'])];
    }
}
