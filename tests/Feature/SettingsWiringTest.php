<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use App\Services\Appointment\AppointmentBookingService;
use App\Services\Appointment\AppointmentRescheduleService;
use App\Services\BranchService;
use App\Services\Inventory\InventoryAlertService;
use App\Services\Inventory\InventoryService;
use App\Services\Promotions\PromotionService;
use App\Services\Settings\SettingService;
use App\View\Composers\ReceiptBusinessDetailsComposer;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\BuildsSalonFixtures;
use Tests\TestCase;

/**
 * Each setting on the Settings pages changes what the system does.
 */
class SettingsWiringTest extends TestCase
{
    use BuildsSalonFixtures;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_auto_confirm_books_new_appointments_as_confirmed(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        [$service, $staff] = $this->bookable($tenant, $branch);
        app(SettingService::class)->setMany($tenant, null, ['booking.auto_confirm' => true], $owner);

        $appointment = $this->book($tenant, $branch, $owner, $service, $staff, '10:00');

        $this->assertSame(Appointment::STATUS_CONFIRMED, $appointment->status);
    }

    public function test_staff_reassignment_can_be_switched_off(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        [$service, $staff] = $this->bookable($tenant, $branch);
        [, $other] = $this->bookable($tenant, $branch, $service);
        $appointment = $this->book($tenant, $branch, $owner, $service, $staff, '10:00');
        app(SettingService::class)->setMany($tenant, null, ['appointment_status.allow_staff_reassignment' => false], $owner);

        $this->expectException(ValidationException::class);
        app(AppointmentRescheduleService::class)->move($appointment, $appointment->starts_at->toImmutable(), $staff->id, $other->id, $owner);
    }

    public function test_new_branches_get_the_default_opening_hours(): void
    {
        [$tenant, $owner] = $this->makeSalon();
        app(SettingService::class)->setMany($tenant, null, ['branch.default_open_time' => '10:00', 'branch.default_close_time' => '20:00'], $owner);

        $branch = app(BranchService::class)->create($tenant, [
            'name' => 'Kandy', 'code' => 'KDY', 'status' => 'active', 'country' => 'LK', 'currency' => 'LKR', 'timezone' => 'Asia/Colombo',
        ]);

        $monday = $branch->businessHours()->where('day_of_week', 1)->firstOrFail();
        $this->assertSame('10:00', substr($monday->opens_at, 0, 5));
        $this->assertSame('20:00', substr($monday->closes_at, 0, 5));
    }

    public function test_receipts_show_the_salon_legal_details(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        app(SettingService::class)->setMany($tenant, null, [
            'business.legal_name' => 'Glow Beauty (Pvt) Ltd',
            'business.registration_number' => 'PV 12345',
            'business.tax_number' => '114567890-7000',
            'business.website' => 'https://glow.lk',
        ], $owner);

        $invoice = new Invoice(['tenant_id' => $tenant->id, 'branch_id' => $branch->id]);
        $invoice->setRelation('tenant', $tenant);
        $view = view('pages.apps.billing.invoices.receipt-thermal', ['invoice' => $invoice]);
        app(ReceiptBusinessDetailsComposer::class)->compose($view);

        $this->assertSame([
            'legal_name' => 'Glow Beauty (Pvt) Ltd',
            'registration_number' => 'PV 12345',
            'tax_number' => '114567890-7000',
            'website' => 'https://glow.lk',
        ], $view->getData()['business']);
    }

    public function test_customer_phone_email_and_number_prefix_follow_the_settings(): void
    {
        [$tenant, $owner] = $this->makeSalon();
        app(SettingService::class)->setMany($tenant, null, [
            'customer.require_phone' => false,
            'customer.require_email' => true,
            'customer.number_prefix' => 'glw',
        ], $owner);

        $this->actingAs($owner)
            ->post(route('customer-management.customers.store'), ['first_name' => 'Nimali', 'status' => 'active'])
            ->assertSessionHasErrors('email')
            ->assertSessionDoesntHaveErrors('phone');

        $this->actingAs($owner)
            ->post(route('customer-management.customers.store'), ['first_name' => 'Nimali', 'email' => 'nimali@example.com', 'status' => 'active'])
            ->assertSessionHasNoErrors();

        $customer = Customer::query()->where('email', 'nimali@example.com')->firstOrFail();
        $this->assertStringStartsWith('GLW-', $customer->customer_code);
    }

    public function test_salon_wide_negative_stock_and_default_reorder_level(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        $product = $this->makeProduct($tenant);
        $inventory = app(InventoryService::class);

        try {
            $inventory->decrease($product, $branch, 1, \App\Models\StockMovement::TYPE_POS_SALE, $owner);
            $this->fail('Selling with no stock should be refused by default.');
        } catch (ValidationException) {
        }

        app(SettingService::class)->setMany($tenant, null, ['inventory.allow_negative_stock' => true, 'inventory.default_low_stock_threshold' => 7], $owner);
        $inventory->decrease($product, $branch, 1, \App\Models\StockMovement::TYPE_POS_SALE, $owner);
        $this->assertSame(-1, (int) Inventory::withoutTenantScope()->where('product_id', $product->id)->value('quantity_on_hand'));

        $this->actingAs($owner)
            ->get(route('inventory.products.create'))
            ->assertOk()
            ->assertViewHas('product', fn (Product $product) => (int) $product->reorder_level === 7);
    }

    public function test_low_stock_alerts_can_be_switched_off(): void
    {
        Notification::fake();
        [$tenant, $owner, $branch] = $this->makeSalon();
        $product = $this->makeProduct($tenant, ['reorder_level' => 10]);
        app(InventoryService::class)->openingStock($product, $branch, 2, 100);

        $this->assertGreaterThan(0, app(InventoryAlertService::class)->sendForTenant($tenant)['low_stock']);

        app(SettingService::class)->setMany($tenant, null, ['inventory.low_stock_alerts' => false], $owner);
        $this->assertSame(0, app(InventoryAlertService::class)->sendForTenant($tenant->fresh())['low_stock']);
    }

    public function test_coupons_can_be_switched_off(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon(['promotions_discounts']);
        app(SettingService::class)->setMany($tenant, null, ['promotion.coupons_enabled' => false], $owner);

        try {
            app(PromotionService::class)->applyToItemSnapshots($tenant, null, $branch, collect(), ['coupon_code' => 'SAVE10']);
            $this->fail('A coupon should be refused.');
        } catch (ValidationException $exception) {
            $this->assertSame('This salon is not accepting coupon codes right now.', $exception->errors()['coupon_code'][0]);
        }
    }

    public function test_login_attempt_limit_follows_the_salon_setting(): void
    {
        [$tenant, $owner] = $this->makeSalon();
        app(SettingService::class)->setMany($tenant, null, ['security.login_attempt_limit' => 2], $owner);

        foreach (range(1, 2) as $attempt) {
            $this->post(route('login'), ['email' => $owner->email, 'password' => 'wrong-password'])
                ->assertSessionHasErrors(['email' => trans('auth.failed')]);
        }

        $this->post(route('login'), ['email' => $owner->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_idle_users_are_signed_out_after_the_salon_timeout(): void
    {
        [$tenant, $owner] = $this->makeSalon();
        app(SettingService::class)->setMany($tenant, null, ['security.session_timeout_minutes' => 30], $owner);

        $this->actingAs($owner)->get(route('dashboard'))->assertOk();
        $this->travel(20)->minutes();
        $this->get(route('dashboard'))->assertOk();

        $this->travel(31)->minutes();
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'You were signed out after 30 minutes without activity.']);
        $this->assertGuest();
    }

    private function bookable($tenant, $branch, $service = null): array
    {
        $service ??= $this->makeService($tenant, $branch, ['name' => 'Hair Cut']);
        $staff = $this->makeStaff($tenant, $branch);
        $staff->services()->attach($service->id, ['tenant_id' => $tenant->id, 'status' => 'active']);

        foreach (range(1, 7) as $day) {
            $staff->schedules()->create([
                'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'day_of_week' => $day,
                'is_working' => true, 'start_time' => '09:00', 'end_time' => '17:00',
            ]);
        }

        return [$service, $staff];
    }

    private function book($tenant, $branch, User $owner, $service, $staff, string $time): Appointment
    {
        $day = now('Asia/Colombo')->next(Carbon::MONDAY)->format('Y-m-d');

        return app(AppointmentBookingService::class)->create($tenant, [
            'branch_id' => $branch->id,
            'customer_id' => $this->makeCustomer($tenant, $branch)->id,
            'booking_source' => 'reception',
            'starts_at' => "{$day} {$time}",
            'services' => [['service_id' => $service->id, 'staff_id' => $staff->id]],
        ], $owner);
    }
}
