<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Promotion;
use App\Models\PromotionCoupon;
use App\Models\PromotionUsage;
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

class PromotionsDiscountManagementTest extends TestCase
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

        Role::firstOrCreate(['name' => 'Cashier', 'guard_name' => 'web'])
            ->givePermissionTo(['billing.view', 'billing.checkout', 'payments.view', 'payments.create', 'promotions.view', 'coupons.view']);
    }

    public function test_coupon_promotion_applies_to_checkout_and_records_usage_after_payment(): void
    {
        [$tenant, $owner, $appointment, $service] = $this->completedAppointmentSetup();
        [$promotion, $coupon] = $this->serviceCouponPromotion($tenant, $owner, $service);
        $cash = $this->cashMethod($tenant);

        $this->actingAs($owner)
            ->post(route('billing.checkout.appointments.store', $appointment), [
                'payment_method_id' => $cash->id,
                'amount' => 2800,
                'coupon_code' => 'WELCOME10',
            ])
            ->assertRedirect();

        $invoice = Invoice::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('appointment_id', $appointment->id)
            ->firstOrFail();

        $this->assertEquals('280.00', $invoice->promotion_discount_amount);
        $this->assertEquals('480.00', $invoice->discount);
        $this->assertEquals('2520.00', $invoice->total);
        $this->assertEquals('2520.00', $invoice->paid_amount);
        $this->assertSame($promotion->id, $invoice->promotion_id);
        $this->assertSame($coupon->id, $invoice->promotion_coupon_id);
        $this->assertSame('WELCOME10', $invoice->promotion_coupon_code);

        $item = $invoice->items()->firstOrFail();
        $this->assertEquals('280.00', $item->promotion_discount_amount);
        $this->assertEquals('480.00', $item->discount_amount);

        $this->assertDatabaseHas('promotion_usages', [
            'tenant_id' => $tenant->id,
            'promotion_id' => $promotion->id,
            'promotion_coupon_id' => $coupon->id,
            'invoice_id' => $invoice->id,
            'status' => PromotionUsage::STATUS_USED,
            'discount_amount' => 280,
        ]);

        $this->assertSame(1, $promotion->fresh()->usage_count);
        $this->assertSame(1, $coupon->fresh()->usage_count);
    }

    public function test_coupon_usage_is_reversed_when_invoice_is_voided(): void
    {
        [$tenant, $owner, $appointment, $service] = $this->completedAppointmentSetup();
        [$promotion, $coupon] = $this->serviceCouponPromotion($tenant, $owner, $service);
        $cash = $this->cashMethod($tenant);

        $this->actingAs($owner)
            ->post(route('billing.checkout.appointments.store', $appointment), [
                'payment_method_id' => $cash->id,
                'amount' => 2800,
                'coupon_code' => 'WELCOME10',
            ])
            ->assertRedirect();

        $invoice = Invoice::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->actingAs($owner)
            ->post(route('billing.invoices.void', $invoice), ['void_reason' => 'Customer refund'])
            ->assertRedirect();

        $usage = PromotionUsage::withoutTenantScope()->where('invoice_id', $invoice->id)->firstOrFail();

        $this->assertSame(PromotionUsage::STATUS_REVERSED, $usage->status);
        $this->assertSame(0, $promotion->fresh()->usage_count);
        $this->assertSame(0, $coupon->fresh()->usage_count);
    }

    public function test_coupon_cannot_be_used_across_tenants(): void
    {
        [$tenant, $owner, $appointment] = $this->completedAppointmentSetup();
        [$otherTenant, $otherOwner, , $otherService] = $this->completedAppointmentSetup();
        $this->serviceCouponPromotion($otherTenant, $otherOwner, $otherService);
        $cash = $this->cashMethod($tenant);

        $this->actingAs($owner)
            ->post(route('billing.checkout.appointments.store', $appointment), [
                'payment_method_id' => $cash->id,
                'amount' => 2800,
                'coupon_code' => 'WELCOME10',
            ])
            ->assertSessionHasErrors('coupon_code');

        $this->assertSame(0, PromotionUsage::withoutTenantScope()->where('tenant_id', $tenant->id)->count());
    }

    public function test_promotions_pages_are_available(): void
    {
        [$tenant, $owner, , $service] = $this->completedAppointmentSetup();
        [$promotion, $coupon] = $this->serviceCouponPromotion($tenant, $owner, $service);

        $this->actingAs($owner)->get(route('promotions.dashboard'))->assertOk()->assertSee('Recent Promotion Usage');
        $this->actingAs($owner)->get(route('promotions.promotions.index'))->assertOk()->assertSee('Promotions');
        $this->actingAs($owner)->get(route('promotions.promotions.show', $promotion))->assertOk()->assertSee($promotion->name);
        $this->actingAs($owner)->get(route('promotions.promotions.edit', $promotion))->assertOk()->assertSee('Promotion Name');
        $this->actingAs($owner)->get(route('promotions.coupons.index'))->assertOk()->assertSee('Coupons');
        $this->actingAs($owner)->get(route('promotions.coupons.edit', $coupon))->assertOk()->assertSee('Coupon Code');
        $this->actingAs($owner)->get(route('promotions.usages.index'))->assertOk()->assertSee('Usage History');
        $this->actingAs($owner)->get(route('promotions.reports.index'))->assertOk()->assertSee('Promotion Performance');
    }

    private function serviceCouponPromotion(Tenant $tenant, User $owner, Service $service): array
    {
        $promotion = Promotion::create([
            'tenant_id' => $tenant->id,
            'name' => 'Welcome Hair Offer',
            'promotion_type' => 'standard',
            'discount_type' => Promotion::DISCOUNT_PERCENTAGE,
            'discount_value' => 10,
            'application_type' => Promotion::APPLICATION_COUPON,
            'coupon_required' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'usage_limit' => 10,
            'per_customer_limit' => 1,
            'customer_scope' => Promotion::CUSTOMER_ALL,
            'branch_scope' => Promotion::BRANCH_ALL,
            'target_scope' => Promotion::TARGET_SERVICES,
            'status' => Promotion::STATUS_ACTIVE,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $promotion->services()->sync([$service->id => ['tenant_id' => $tenant->id]]);

        $coupon = PromotionCoupon::create([
            'tenant_id' => $tenant->id,
            'promotion_id' => $promotion->id,
            'code' => 'WELCOME10',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
            'usage_limit' => 10,
            'per_customer_limit' => 1,
            'status' => PromotionCoupon::STATUS_ACTIVE,
            'created_by' => $owner->id,
        ]);

        return [$promotion, $coupon];
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
                'promotions_discounts' => true,
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

        return [$tenant, $owner, $appointment->fresh(['tenant', 'branch', 'customer', 'appointmentServices.service']), $service, $staff, $branch, $customer];
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
