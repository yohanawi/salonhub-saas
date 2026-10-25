<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\CustomerConsultation;
use App\Models\CustomerLoyaltyAccount;
use App\Models\CustomerMembership;
use App\Models\CustomerPhoto;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MembershipPlan;
use App\Models\MessageLog;
use App\Models\PaymentMethod;
use App\Services\Billing\PaymentService;
use App\Services\Customers\CustomerSegmentService;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Settings\SettingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\BuildsSalonFixtures;
use Tests\TestCase;

class CustomerEngagementTest extends TestCase
{
    use BuildsSalonFixtures;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        Queue::fake();
    }

    public function test_smart_segments_and_customer_list_filter(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon(['loyalty_membership']);
        app(SettingService::class)->setMany($tenant, null, ['customer.vip_spend_threshold' => 20000, 'customer.inactive_days' => 60, 'customer.new_days' => 30], $owner);

        $vip = $this->makeCustomer($tenant, $branch);
        $this->invoice($tenant, $branch, $vip, 25000, now()->subDays(10));

        $lapsed = $this->makeCustomer($tenant, $branch);
        $lapsed->forceFill(['created_at' => now()->subYear()])->save();
        $this->invoice($tenant, $branch, $lapsed, 3000, now()->subDays(90));

        $old = $this->makeCustomer($tenant, $branch);
        $old->forceFill(['created_at' => now()->subYear(), 'date_of_birth' => now()->subYears(30)->toDateString()])->save();
        $this->invoice($tenant, $branch, $old, 1000, now()->subDays(5));

        $plan = MembershipPlan::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'name' => 'Club', 'code' => 'club', 'price' => 1000, 'duration_type' => 'months', 'duration_value' => 6, 'status' => 'active']);
        CustomerMembership::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'customer_id' => $old->id, 'membership_plan_id' => $plan->id, 'start_date' => now()->subDay()->toDateString(), 'end_date' => now()->addMonth()->toDateString(), 'status' => 'active']);

        $segments = app(CustomerSegmentService::class);
        $ids = fn (string $segment) => $segments->query($tenant, $segment)->pluck('customers.id')->sort()->values()->all();

        $this->assertSame([$vip->id], $ids('vip'));
        $this->assertSame([$lapsed->id], $ids('inactive'));
        $this->assertContains($vip->id, $ids('new'));
        $this->assertNotContains($lapsed->id, $ids('new'));
        $this->assertSame([$old->id], $ids('birthday_month'));
        $this->assertSame([$old->id], $ids('members'));
        $this->assertSame(['birthday_month', 'members'], $segments->segmentsFor($old->fresh()));

        $this->actingAs($owner)->get(route('customer-management.customers.index', ['segment' => 'inactive']))
            ->assertOk()->assertSee($lapsed->customer_code)->assertDontSee($vip->customer_code);
        $this->actingAs($owner)->get(route('customer-management.segments.index'))->assertOk()->assertSee('Inactive')->assertSee('No visit in the last 60 days');
        $this->actingAs($owner)->get(route('customer-management.customers.show', $old))->assertOk()->assertSee('Birthdays this month');
    }

    public function test_campaign_messages_consenting_customers_in_a_segment(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        $optedIn = $this->makeCustomer($tenant, $branch);
        $optedIn->update(['marketing_consent' => true]);
        $optedOut = $this->makeCustomer($tenant, $branch);

        // SMS not set up yet.
        $this->actingAs($owner)->post(route('customer-management.segments.campaign'), ['segment' => 'new', 'name' => 'Welcome', 'message' => 'Hi {customer_name}!'])
            ->assertSessionHasErrors('message');

        $this->enableSms($tenant, $owner);
        $this->actingAs($owner)->post(route('customer-management.segments.campaign'), ['segment' => 'new', 'name' => 'Welcome', 'message' => 'Hi {customer_name}, welcome to {salon_name}!'])
            ->assertRedirect()->assertSessionHas('status');

        $logs = MessageLog::withoutTenantScope()->where('tenant_id', $tenant->id)->where('event', 'campaign')->get();
        $this->assertCount(1, $logs);
        $this->assertSame($optedIn->id, (int) $logs->first()->related_id);
        $this->assertStringContainsString('Hi Emma, welcome to ' . $tenant->name, $logs->first()->body);
        $this->assertNotNull($optedOut);

        $this->actingAs($owner)->get(route('customer-management.segments.index'))->assertOk()->assertSee('Welcome');
    }

    public function test_referral_rewards_both_customers_once_on_first_paid_visit(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon(['loyalty_membership']);
        $loyalty = app(LoyaltyService::class);
        $loyalty->ensureDefaultRule($loyalty->ensureDefaultProgram($tenant, $owner));

        $this->actingAs($owner)->post(route('customer-management.customers.store'), ['first_name' => 'Nadee', 'phone' => '0770000001', 'status' => 'active', 'branch_id' => $branch->id])->assertRedirect();
        $referrer = Customer::withoutTenantScope()->where('tenant_id', $tenant->id)->where('first_name', 'Nadee')->firstOrFail();
        $this->assertNotNull($referrer->referral_code);

        $this->actingAs($owner)->post(route('customer-management.customers.store'), ['first_name' => 'Kasun', 'phone' => '0770000002', 'status' => 'active', 'referral_code_used' => 'NOPE999'])
            ->assertSessionHasErrors('referral_code');

        $this->actingAs($owner)->post(route('customer-management.customers.store'), ['first_name' => 'Kasun', 'phone' => '0770000002', 'status' => 'active', 'branch_id' => $branch->id, 'referral_code_used' => strtolower($referrer->referral_code)])->assertRedirect();
        $friend = Customer::withoutTenantScope()->where('tenant_id', $tenant->id)->where('first_name', 'Kasun')->firstOrFail();
        $this->assertSame($referrer->id, $friend->referred_by_customer_id);

        $cash = PaymentMethod::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'code' => 'cash', 'name' => 'Cash', 'type' => PaymentMethod::TYPE_CASH, 'is_active' => true, 'requires_reference' => false]);
        $payments = app(PaymentService::class);

        $payments->record($this->invoice($tenant, $branch, $friend, 1000, now(), paid: false), $cash, ['amount' => 1000], $owner);
        $payments->record($this->invoice($tenant, $branch, $friend, 1000, now(), paid: false), $cash, ['amount' => 1000], $owner);

        $points = fn (Customer $customer) => (int) CustomerLoyaltyAccount::withoutTenantScope()->where('customer_id', $customer->id)->value('available_points');
        $this->assertSame(200, $points($referrer));
        $this->assertSame(100 + 20, $points($friend)); // welcome bonus + 10 points per LKR 1,000 visit
        $this->assertNotNull($friend->fresh()->referral_rewarded_at);

        $this->actingAs($owner)->get(route('customer-management.customers.show', $referrer))->assertOk()->assertSee($referrer->referral_code)->assertSee('Kasun');
        $this->actingAs($owner)->get(route('customer-management.segments.index'))->assertOk()->assertSee('1 referred');

        // A customer cannot refer themselves.
        $self = $this->makeCustomer($tenant, $branch);
        $self->update(['referral_code' => 'SELF123']);
        $this->actingAs($owner)->put(route('customer-management.customers.update', $self), ['first_name' => 'Emma', 'phone' => $self->phone, 'status' => 'active', 'referral_code_used' => 'SELF123'])
            ->assertSessionHasErrors('referral_code');
    }

    public function test_allergies_photos_and_consultation_forms(): void
    {
        Storage::fake('local');
        [$tenant, $owner, $branch] = $this->makeSalon();
        [, $otherOwner] = $this->makeSalon();
        $customer = $this->makeCustomer($tenant, $branch);

        $this->actingAs($owner)->put(route('customer-management.customers.update', $customer), [
            'first_name' => 'Emma', 'phone' => $customer->phone, 'status' => 'active', 'allergies' => 'PPD hair dye', 'anniversary_date' => '2020-06-15',
        ])->assertRedirect();
        $customer->refresh();
        $this->assertSame('PPD hair dye', $customer->allergies);
        $this->assertSame('2020-06-15', $customer->anniversary_date->toDateString());

        $appointment = Appointment::withoutTenantScope()->create([
            'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'customer_id' => $customer->id, 'appointment_number' => 'APT-' . fake()->unique()->numberBetween(100000, 999999),
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(), 'status' => Appointment::STATUS_CONFIRMED,
        ]);
        $this->actingAs($owner)->get(route('appointment-management.appointments.show', $appointment))->assertOk()->assertSee('PPD hair dye');
        $this->actingAs($owner)->get(route('customer-management.customers.show', $customer))->assertOk()->assertSee('Allergies & sensitivities', false);

        $this->actingAs($owner)->post(route('customer-management.photos.store', $customer), [
            'photos' => [UploadedFile::fake()->image('before.jpg'), UploadedFile::fake()->image('after.jpg')],
            'kind' => 'before', 'caption' => 'Level 6 base', 'appointment_id' => $appointment->id,
        ])->assertRedirect();
        $photo = CustomerPhoto::withoutTenantScope()->where('customer_id', $customer->id)->firstOrFail();
        $this->assertSame(2, CustomerPhoto::withoutTenantScope()->where('customer_id', $customer->id)->count());
        Storage::disk('local')->assertExists($photo->path);

        $this->actingAs($owner)->get(route('customer-management.photos.show', [$customer, $photo]))->assertOk();
        $this->actingAs($otherOwner)->get(route('customer-management.photos.show', [$customer, $photo]))->assertNotFound();

        $this->actingAs($owner)->delete(route('customer-management.photos.destroy', [$customer, $photo]))->assertRedirect();
        Storage::disk('local')->assertMissing($photo->path);

        $this->actingAs($owner)->get(route('customer-management.consultations.create', $customer))->assertOk()->assertSee('Consultation & Consent Form', false);
        $this->actingAs($owner)->post(route('customer-management.consultations.store', $customer), ['answers' => ['allergies' => 'PPD'], 'consent_name' => 'Emma'])
            ->assertSessionHasErrors('consent_given');

        $this->actingAs($owner)->post(route('customer-management.consultations.store', $customer), [
            'appointment_id' => $appointment->id,
            'answers' => ['hair_type' => 'Curly', 'pregnant_or_breastfeeding' => 'yes', 'patch_test_done' => 'no', 'allergies' => 'PPD hair dye, latex'],
            'consent_given' => 1, 'consent_name' => 'Emma Silva', 'update_allergies' => 1,
        ])->assertRedirect();

        $consultation = CustomerConsultation::withoutTenantScope()->where('customer_id', $customer->id)->firstOrFail();
        $this->assertSame('Curly', $consultation->answers['hair_type']);
        $this->assertContains('Pregnant or breastfeeding', $consultation->warnings());
        $this->assertSame('PPD hair dye, latex', $customer->fresh()->allergies);

        $this->actingAs($owner)->get(route('customer-management.consultations.show', [$customer, $consultation]))->assertOk()->assertSee('Emma Silva')->assertSee('No patch test');
    }

    public function test_anniversary_greetings_are_sent_once(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        $this->enableSms($tenant, $owner);
        app(SettingService::class)->setMany($tenant, null, ['promotion.anniversary_greeting_enabled' => true], $owner);

        $today = CarbonImmutable::now('Asia/Colombo');
        $couple = $this->makeCustomer($tenant, $branch);
        $couple->update(['anniversary_date' => $today->subYears(5)->toDateString()]);
        $other = $this->makeCustomer($tenant, $branch);
        $other->update(['anniversary_date' => $today->subYears(5)->addDays(3)->toDateString()]);

        $this->artisan('customers:birthday-greetings', ['--tenant_id' => $tenant->id])->assertSuccessful();
        $this->artisan('customers:birthday-greetings', ['--tenant_id' => $tenant->id])->assertSuccessful();

        $logs = MessageLog::withoutTenantScope()->where('tenant_id', $tenant->id)->where('event', 'anniversary_greeting')->get();
        $this->assertCount(1, $logs);
        $this->assertSame($couple->id, (int) $logs->first()->related_id);
        $this->assertStringContainsString('Happy anniversary Emma', $logs->first()->body);
    }

    private function invoice($tenant, $branch, Customer $customer, float $amount, $issuedAt, bool $paid = true): Invoice
    {
        $invoice = Invoice::withoutTenantScope()->create([
            'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'customer_id' => $customer->id,
            'invoice_number' => 'INV-' . fake()->unique()->numberBetween(100000, 999999),
            'subtotal' => $amount, 'total' => $amount, 'paid_amount' => $paid ? $amount : 0, 'balance_amount' => $paid ? 0 : $amount,
            'status' => Invoice::STATUS_ISSUED, 'payment_status' => $paid ? Invoice::PAYMENT_PAID : Invoice::PAYMENT_UNPAID,
            'issued_at' => $issuedAt, 'paid_at' => $paid ? $issuedAt : null,
        ]);

        InvoiceItem::withoutTenantScope()->create([
            'tenant_id' => $tenant->id, 'sale_id' => $invoice->id, 'item_type' => 'service', 'item_id' => 0, 'item_name' => 'Hair Cut',
            'quantity' => 1, 'unit_price' => $amount, 'gross_amount' => $amount, 'net_amount' => $amount, 'total_amount' => $amount, 'total' => $amount,
        ]);

        return $invoice->fresh(['tenant', 'customer', 'items']);
    }
}
