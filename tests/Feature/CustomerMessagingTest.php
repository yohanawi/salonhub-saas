<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\MessageLog;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Messaging\CustomerNotifier;
use App\Services\Messaging\PhoneNumber;
use App\Services\Settings\SettingService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CustomerMessagingTest extends TestCase
{
    use DatabaseTransactions;

    private Tenant $tenant;
    private User $owner;
    private Branch $branch;
    private Service $service;
    private Staff $stylist;
    private Customer $customer;
    private PaymentMethod $cash;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (RolesPermissionsSeeder::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'Salon Owner', 'guard_name' => 'web'])
            ->givePermissionTo(RolesPermissionsSeeder::PERMISSIONS);

        Http::preventStrayRequests();
        $this->salonSetup();
    }

    public function test_local_numbers_are_turned_into_international_digits(): void
    {
        $this->assertSame('94771234567', PhoneNumber::normalize('077 123 4567'));
        $this->assertSame('94771234567', PhoneNumber::normalize('+94 77 123 4567'));
        $this->assertSame('94771234567', PhoneNumber::normalize('771234567'));
        $this->assertSame('94771234567', PhoneNumber::normalize('0094771234567'));
        $this->assertNull(PhoneNumber::normalize('1234'));
        $this->assertNull(PhoneNumber::normalize(null));
    }

    public function test_nothing_is_sent_when_messaging_is_not_set_up(): void
    {
        $this->sale();

        $this->assertSame(0, MessageLog::withoutTenantScope()->where('tenant_id', $this->tenant->id)->count());
        Http::assertNothingSent();
    }

    public function test_receipt_is_texted_through_notify_lk_after_a_sale(): void
    {
        $this->enableNotifyLk();
        Http::fake(['app.notify.lk/*' => Http::response(['status' => 'success', 'data' => 'Sent'])]);

        $this->sale();
        $invoice = Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->latest('id')->firstOrFail();

        $log = MessageLog::withoutTenantScope()->where('tenant_id', $this->tenant->id)->firstOrFail();
        $this->assertSame(MessageLog::STATUS_SENT, $log->status);
        $this->assertSame('notify_lk', $log->provider);
        $this->assertSame(MessageLog::EVENT_RECEIPT, $log->event);
        $this->assertSame('94771234567', $log->recipient);
        $this->assertStringContainsString($invoice->invoice_number, $log->body);
        $this->assertStringContainsString('/r/' . $invoice->id . '?', $log->body);

        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://app.notify.lk/api/v1/send')
            && $request['user_id'] === '12345'
            && $request['api_key'] === 'secret-key'
            && $request['sender_id'] === 'GlamSalon'
            && $request['to'] === '94771234567');
    }

    public function test_provider_failure_is_logged_without_breaking_the_sale(): void
    {
        $this->enableNotifyLk();
        Http::fake(['app.notify.lk/*' => Http::response(['status' => 'error', 'errors' => ['Insufficient credit']])]);

        $this->sale();

        $this->assertSame(1, Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->count());
        $log = MessageLog::withoutTenantScope()->where('tenant_id', $this->tenant->id)->firstOrFail();
        $this->assertSame(MessageLog::STATUS_FAILED, $log->status);
        $this->assertStringContainsString('Insufficient credit', $log->error);
    }

    public function test_whatsapp_falls_back_to_sms_when_it_fails(): void
    {
        $this->enableNotifyLk();
        $this->enableWhatsApp(['notification.channel' => 'whatsapp_then_sms']);
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['message' => 'Template not approved']], 400),
            'app.notify.lk/*' => Http::response(['status' => 'success', 'data' => 'Sent']),
        ]);

        $this->sale();

        $logs = MessageLog::withoutTenantScope()->where('tenant_id', $this->tenant->id)->orderBy('id')->get();
        $this->assertSame([MessageLog::CHANNEL_WHATSAPP, MessageLog::CHANNEL_SMS], $logs->pluck('channel')->all());
        $this->assertSame([MessageLog::STATUS_FAILED, MessageLog::STATUS_SENT], $logs->pluck('status')->all());
    }

    public function test_whatsapp_uses_the_approved_template_for_receipts(): void
    {
        $this->enableWhatsApp(['notification.channel' => 'whatsapp', 'integration.whatsapp_receipt_template' => 'salon_receipt']);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.123']]])]);

        $this->sale();

        $log = MessageLog::withoutTenantScope()->where('tenant_id', $this->tenant->id)->firstOrFail();
        $this->assertSame(MessageLog::STATUS_SENT, $log->status);
        $this->assertSame('wamid.123', $log->provider_message_id);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://graph.facebook.com/v20.0/1098765/messages'
            && $request->hasHeader('Authorization', 'Bearer wa-token')
            && $request['type'] === 'template'
            && $request['template']['name'] === 'salon_receipt'
            && $request['template']['components'][0]['parameters'][0]['text'] === 'Emma');
    }

    public function test_twilio_sends_with_basic_auth_and_plus_number(): void
    {
        app(SettingService::class)->setMany($this->tenant, null, [
            'notification.sms_enabled' => true,
            'integration.sms_provider' => 'twilio',
            'integration.sms_user_id' => 'AC123',
            'integration.sms_api_key' => 'twilio-token',
            'integration.sms_sender_id' => '+15005550006',
        ], $this->owner);
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM1'], 201)]);

        $this->sale();

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.twilio.com/2010-04-01/Accounts/AC123/Messages.json'
            && $request['To'] === '+94771234567'
            && $request->hasHeader('Authorization', 'Basic ' . base64_encode('AC123:twilio-token')));
        $this->assertSame('SM1', MessageLog::withoutTenantScope()->where('tenant_id', $this->tenant->id)->value('provider_message_id'));
    }

    public function test_receipts_can_be_switched_off(): void
    {
        $this->enableNotifyLk(['notification.send_receipts' => false]);
        Http::fake();

        $this->sale();

        Http::assertNothingSent();
    }

    public function test_confirming_a_booking_texts_the_customer(): void
    {
        $this->enableNotifyLk();
        Http::fake(['app.notify.lk/*' => Http::response(['status' => 'success', 'data' => 'Sent'])]);

        $appointment = $this->appointment(Appointment::STATUS_PENDING, now()->addDays(3));
        $this->assertSame(0, MessageLog::withoutTenantScope()->where('tenant_id', $this->tenant->id)->count());

        $appointment->update(['status' => Appointment::STATUS_CONFIRMED]);
        $appointment->update(['notes' => 'Bring photos']);

        $log = MessageLog::withoutTenantScope()->where('tenant_id', $this->tenant->id)->sole();
        $this->assertSame(MessageLog::EVENT_BOOKING_CONFIRMATION, $log->event);
        $this->assertSame(Appointment::class, $log->related_type);
        $this->assertStringContainsString('is confirmed for', $log->body);
    }

    public function test_reminders_go_out_once_inside_the_reminder_window(): void
    {
        $this->enableNotifyLk(['notification.booking_reminder_hours' => 24]);
        Http::fake(['app.notify.lk/*' => Http::response(['status' => 'success', 'data' => 'Sent'])]);

        $soon = $this->appointment(Appointment::STATUS_CONFIRMED, now()->addHours(5));
        $later = $this->appointment(Appointment::STATUS_CONFIRMED, now()->addDays(3));
        $cancelled = $this->appointment(Appointment::STATUS_CANCELLED, now()->addHours(2));
        MessageLog::withoutTenantScope()->where('tenant_id', $this->tenant->id)->delete();

        $this->artisan('appointments:send-reminders')->assertSuccessful();
        $this->artisan('appointments:send-reminders')->assertSuccessful();

        $reminders = MessageLog::withoutTenantScope()->where('tenant_id', $this->tenant->id)->where('event', MessageLog::EVENT_REMINDER)->get();
        $this->assertCount(1, $reminders);
        $this->assertSame($soon->id, $reminders->first()->related_id);
        $this->assertNotNull($soon->fresh()->reminder_sent_at);
        $this->assertNull($later->fresh()->reminder_sent_at);
        $this->assertNull($cancelled->fresh()->reminder_sent_at);
    }

    public function test_receipt_link_opens_without_login_only_when_signed(): void
    {
        $this->sale();
        $invoice = Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->latest('id')->firstOrFail();
        $url = app(CustomerNotifier::class)->receiptUrl($invoice);

        auth()->logout();

        $this->get($url)->assertOk()->assertSee($invoice->invoice_number)->assertDontSee('window.close()', false);
        $this->get(route('receipts.show', ['invoice' => $invoice->id]))->assertForbidden();
    }

    public function test_receipt_can_be_sent_again_from_the_invoice_page(): void
    {
        $this->sale();
        $invoice = Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->latest('id')->firstOrFail();

        $this->enableNotifyLk(['notification.send_receipts' => false]);
        Http::fake(['app.notify.lk/*' => Http::response(['status' => 'success', 'data' => 'Sent'])]);

        $this->actingAs($this->owner)->get(route('billing.invoices.show', $invoice))->assertOk()->assertSee('Send Receipt');

        $this->actingAs($this->owner)
            ->post(route('billing.invoices.send-receipt', $invoice), ['phone' => '0711112222'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Receipt sent to 94711112222.');
    }

    public function test_message_log_page_and_test_send(): void
    {
        $this->actingAs($this->owner)
            ->post(route('settings.messages.test'), ['phone' => '0771234567'])
            ->assertSessionHasErrors(['phone' => 'Turn on SMS or WhatsApp and add the provider details in Settings first.']);

        $this->enableNotifyLk();
        Http::fake(['app.notify.lk/*' => Http::response(['status' => 'success', 'data' => 'Sent'])]);

        $this->actingAs($this->owner)
            ->post(route('settings.messages.test'), ['phone' => '0771234567'])
            ->assertSessionHas('status', 'Test message sent to 94771234567.');

        $this->actingAs($this->owner)
            ->get(route('settings.messages.index'))
            ->assertOk()
            ->assertSee('SMS / WhatsApp Messages')
            ->assertSee('+94771234567');
    }

    // ---------------------------------------------------------------- Helpers

    private function enableNotifyLk(array $extra = []): void
    {
        app(SettingService::class)->setMany($this->tenant, null, $extra + [
            'notification.sms_enabled' => true,
            'integration.sms_provider' => 'notify_lk',
            'integration.sms_user_id' => '12345',
            'integration.sms_api_key' => 'secret-key',
            'integration.sms_sender_id' => 'GlamSalon',
        ], $this->owner);
    }

    private function enableWhatsApp(array $extra = []): void
    {
        app(SettingService::class)->setMany($this->tenant, null, $extra + [
            'notification.whatsapp_enabled' => true,
            'integration.whatsapp_provider' => 'meta',
            'integration.whatsapp_access_token' => 'wa-token',
            'integration.whatsapp_phone_number_id' => '1098765',
        ], $this->owner);
    }

    private function sale(): void
    {
        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), [
                'customer_mode' => 'existing',
                'customer_id' => $this->customer->id,
                'service_items' => [['service_id' => $this->service->id, 'staff_id' => $this->stylist->id, 'quantity' => 1]],
                'payment_method_id' => $this->cash->id,
                'amount' => 3000,
            ])
            ->assertSessionHasNoErrors();
    }

    private function appointment(string $status, $startsAt): Appointment
    {
        return Appointment::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->stylist->id,
            'appointment_number' => 'APT-' . fake()->unique()->numerify('######'),
            'booking_source' => 'reception',
            'source' => 'reception',
            'status' => $status,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
            'subtotal' => 3000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 3000,
            'created_by' => $this->owner->id,
            'updated_by' => $this->owner->id,
        ]);
    }

    private function salonSetup(): void
    {
        $this->tenant = Tenant::create([
            'name' => 'Glam Salon',
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
            'tenant_id' => $this->tenant->id,
            'plan_id' => $plan->id,
            'status' => 'trialing',
            'price' => 0,
            'billing_period' => 'trial',
            'entitlements' => $plan->entitlementSnapshot(),
            'starts_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);

        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'email_verified_at' => now()]);
        $this->owner->assignRole('Salon Owner');

        $this->branch = Branch::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Colombo',
            'code' => 'CMB',
            'phone' => '0112345678',
            'status' => Branch::STATUS_ACTIVE,
            'is_active' => true,
            'is_main' => true,
            'country' => 'LK',
            'currency' => 'LKR',
            'timezone' => 'Asia/Colombo',
        ]);

        $this->service = Service::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Hair Cut',
            'slug' => 'hair-cut',
            'price' => 3000,
            'duration_minutes' => 60,
            'default_price' => 3000,
            'default_duration_minutes' => 60,
            'is_active' => true,
        ]);
        $this->service->branches()->attach($this->branch->id, ['tenant_id' => $this->tenant->id, 'price' => 3000, 'duration_minutes' => 60, 'is_active' => true]);

        $this->stylist = Staff::create([
            'tenant_id' => $this->tenant->id,
            'employee_code' => 'STF-0001',
            'first_name' => 'Nadeesha',
            'last_name' => 'Perera',
            'job_title' => 'Stylist',
            'status' => Staff::STATUS_ACTIVE,
            'is_bookable' => true,
        ]);
        $this->stylist->branches()->attach($this->branch->id, ['tenant_id' => $this->tenant->id, 'is_primary' => true, 'status' => 'active']);
        $this->stylist->services()->attach($this->service->id, ['tenant_id' => $this->tenant->id, 'status' => 'active']);

        $this->customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUS-000001',
            'first_name' => 'Emma',
            'last_name' => 'Silva',
            'phone' => '077 123 4567',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $this->cash = PaymentMethod::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Cash',
            'code' => 'cash',
            'type' => PaymentMethod::TYPE_CASH,
            'is_active' => true,
        ]);
    }
}
