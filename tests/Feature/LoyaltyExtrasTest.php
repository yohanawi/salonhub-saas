<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerLoyaltyAccount;
use App\Models\CustomerMembership;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LoyaltyTier;
use App\Models\MembershipPlan;
use App\Models\MessageLog;
use App\Models\PaymentMethod;
use App\Services\Loyalty\CustomerRewardsService;
use App\Services\Loyalty\LoyaltyService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\Concerns\BuildsSalonFixtures;
use Tests\TestCase;

class LoyaltyExtrasTest extends TestCase
{
    use BuildsSalonFixtures;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        Queue::fake();
    }

    public function test_tiers_are_reached_by_lifetime_points_and_boost_earning(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon(['loyalty_membership']);
        $loyalty = app(LoyaltyService::class);
        $program = $loyalty->ensureDefaultProgram($tenant, $owner);
        $loyalty->ensureDefaultRule($program); // 1 point per LKR 100
        $customer = $this->makeCustomer($tenant, $branch);

        $this->actingAs($owner)->post(route('loyalty-management.tiers.store'), ['name' => 'Silver', 'min_points' => 0, 'points_multiplier' => 1, 'color' => 'secondary'])->assertRedirect();
        $this->actingAs($owner)->post(route('loyalty-management.tiers.store'), ['name' => 'Gold', 'min_points' => 100, 'points_multiplier' => 2, 'color' => 'warning', 'benefits' => 'Free hair wash'])->assertRedirect();
        // Duplicate thresholds are refused.
        $this->actingAs($owner)->post(route('loyalty-management.tiers.store'), ['name' => 'Bronze', 'min_points' => 100, 'points_multiplier' => 1, 'color' => 'dark'])->assertSessionHasErrors('min_points');

        $loyalty->earnFromInvoice($this->paidInvoice($tenant, $branch, $customer, 10000));
        $account = CustomerLoyaltyAccount::withoutTenantScope()->with('tier')->where('customer_id', $customer->id)->firstOrFail();

        $this->assertSame(100, $account->available_points);
        $this->assertSame('Gold', $account->tier->name);

        // Gold earns double from the next visit on.
        $loyalty->earnFromInvoice($this->paidInvoice($tenant, $branch, $customer, 1000));
        $this->assertSame(120, $account->fresh()->available_points);

        // Raising the Gold threshold re-places existing members.
        $gold = LoyaltyTier::withoutTenantScope()->where('name', 'Gold')->firstOrFail();
        $this->actingAs($owner)->put(route('loyalty-management.tiers.update', $gold), ['name' => 'Gold', 'min_points' => 500, 'points_multiplier' => 2, 'color' => 'warning'])->assertRedirect();
        $this->assertSame('Silver', $account->fresh()->tier->name);

        $this->actingAs($owner)->get(route('loyalty-management.tiers.index'))->assertOk()->assertSee('Gold')->assertSee('500+ lifetime points');
    }

    public function test_customer_can_see_points_by_sms_and_rewards_page(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon(['loyalty_membership']);
        $this->enableSms($tenant, $owner);
        $loyalty = app(LoyaltyService::class);
        $program = $loyalty->ensureDefaultProgram($tenant, $owner);
        $loyalty->ensureDefaultRule($program);
        LoyaltyTier::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'loyalty_program_id' => $program->id, 'name' => 'Silver', 'min_points' => 0, 'points_multiplier' => 1, 'color' => 'secondary']);
        LoyaltyTier::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'loyalty_program_id' => $program->id, 'name' => 'Gold', 'min_points' => 1000, 'points_multiplier' => 1.5, 'color' => 'warning']);
        $customer = $this->makeCustomer($tenant, $branch);

        $loyalty->earnFromInvoice($this->paidInvoice($tenant, $branch, $customer, 25000));

        $this->actingAs($owner)->post(route('loyalty-management.customers.send-points', $customer))->assertRedirect()->assertSessionHas('status');

        $log = MessageLog::withoutTenantScope()->where('tenant_id', $tenant->id)->where('event', 'points_balance')->firstOrFail();
        $this->assertStringContainsString('you have 250 points', $log->body);
        $this->assertStringContainsString('worth LKR 1,250.00', $log->body);
        $this->assertStringContainsString('Silver member', $log->body);
        $this->assertStringContainsString('/rewards/' . $customer->id, $log->body);

        $url = app(CustomerRewardsService::class)->portalUrl($customer);
        auth()->logout();
        $this->get($url)->assertOk()->assertSee('250')->assertSee('Silver member')->assertSee('750 more points to reach Gold', false);
        $this->get(route('rewards.show', ['customer' => $customer->id]))->assertForbidden();

        $this->actingAs($owner)->get(route('customer-management.customers.show', $customer))->assertOk()->assertSee('Text Points Balance')->assertSee('Copy Rewards Link');
    }

    public function test_points_are_texted_automatically_after_a_paid_visit_when_enabled(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon(['loyalty_membership']);
        $this->enableSms($tenant, $owner);
        app(\App\Services\Settings\SettingService::class)->setMany($tenant, null, ['loyalty.notify_points_earned' => true], $owner);
        $loyalty = app(LoyaltyService::class);
        $loyalty->ensureDefaultRule($loyalty->ensureDefaultProgram($tenant, $owner));
        $customer = $this->makeCustomer($tenant, $branch);
        $cash = PaymentMethod::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'code' => 'cash', 'name' => 'Cash', 'type' => PaymentMethod::TYPE_CASH, 'is_active' => true, 'requires_reference' => false]);

        $invoice = $this->paidInvoice($tenant, $branch, $customer, 5000, paid: false);
        app(\App\Services\Billing\PaymentService::class)->record($invoice, $cash, ['amount' => 5000], $owner);

        $this->assertTrue(MessageLog::withoutTenantScope()->where('tenant_id', $tenant->id)->where('event', 'points_balance')->exists());
    }

    public function test_auto_renewal_creates_next_term_with_unpaid_invoice_once(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon(['loyalty_membership']);
        $customer = $this->makeCustomer($tenant, $branch);
        $plan = $this->plan($tenant);

        $membership = $this->membership($tenant, $customer, $plan, today()->subMonths(6), today()->subDay(), autoRenew: true);
        $manual = $this->membership($tenant, $this->makeCustomer($tenant, $branch), $plan, today()->subMonths(6), today()->subDay(), autoRenew: false);

        $this->artisan('memberships:renew', ['--tenant_id' => $tenant->id])->assertSuccessful();
        $this->artisan('memberships:renew', ['--tenant_id' => $tenant->id])->assertSuccessful();

        $membership->refresh();
        $this->assertNotNull($membership->renewed_membership_id);
        $this->assertSame(CustomerMembership::STATUS_EXPIRED, $membership->status);

        $renewal = CustomerMembership::withoutTenantScope()->with('invoice')->findOrFail($membership->renewed_membership_id);
        $this->assertSame(CustomerMembership::STATUS_ACTIVE, $renewal->status);
        $this->assertSame(today()->toDateString(), $renewal->start_date->toDateString());
        $this->assertSame(today()->addMonths(6)->subDay()->toDateString(), $renewal->end_date->toDateString());
        $this->assertTrue($renewal->auto_renew);
        $this->assertSame(Invoice::PAYMENT_UNPAID, $renewal->invoice->payment_status);
        $this->assertEquals('12000.00', $renewal->invoice->total);

        $this->assertSame(2, CustomerMembership::withoutTenantScope()->where('customer_id', $customer->id)->count());
        $this->assertNull($manual->fresh()->renewed_membership_id);

        $this->actingAs($owner)->get(route('loyalty-management.memberships.show', $renewal))->assertOk()->assertSee('Renewed from');
    }

    public function test_manual_renewal_with_payment_and_expiry_reminders(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon(['loyalty_membership']);
        $this->enableSms($tenant, $owner);
        $customer = $this->makeCustomer($tenant, $branch);
        $plan = $this->plan($tenant);
        $cash = PaymentMethod::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'code' => 'cash', 'name' => 'Cash', 'type' => PaymentMethod::TYPE_CASH, 'is_active' => true, 'requires_reference' => false]);

        $membership = $this->membership($tenant, $customer, $plan, today()->subMonths(6)->addDays(3), today()->addDays(3), autoRenew: true);

        $this->artisan('memberships:send-reminders', ['--tenant_id' => $tenant->id])->assertSuccessful();
        $this->artisan('memberships:send-reminders', ['--tenant_id' => $tenant->id])->assertSuccessful();

        $reminders = MessageLog::withoutTenantScope()->where('tenant_id', $tenant->id)->where('event', 'membership_reminder')->get();
        $this->assertCount(1, $reminders);
        $this->assertStringContainsString('renew automatically', $reminders->first()->body);

        $this->actingAs($owner)->post(route('loyalty-management.memberships.renew', $membership), ['payment_method_id' => $cash->id])->assertRedirect();

        $renewal = CustomerMembership::withoutTenantScope()->with('invoice')->findOrFail($membership->fresh()->renewed_membership_id);
        $this->assertSame($membership->end_date->addDay()->toDateString(), $renewal->start_date->toDateString());
        $this->assertSame(Invoice::PAYMENT_PAID, $renewal->invoice->payment_status);
        // The current term is still running, so it stays active until it ends.
        $this->assertSame(CustomerMembership::STATUS_ACTIVE, $membership->fresh()->status);

        // Renewing twice is refused.
        $this->actingAs($owner)->post(route('loyalty-management.memberships.renew', $membership))->assertSessionHasErrors('membership');

        $this->actingAs($owner)->post(route('loyalty-management.memberships.auto-renew', $renewal))->assertRedirect();
        $this->assertFalse($renewal->fresh()->auto_renew);
    }

    private function plan($tenant): MembershipPlan
    {
        return MembershipPlan::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Glow Club',
            'code' => 'glow-' . fake()->unique()->numberBetween(100, 999),
            'price' => 12000,
            'duration_type' => 'months',
            'duration_value' => 6,
            'billing_type' => 'one_time',
            'status' => MembershipPlan::STATUS_ACTIVE,
        ]);
    }

    private function membership($tenant, Customer $customer, MembershipPlan $plan, $start, $end, bool $autoRenew): CustomerMembership
    {
        return CustomerMembership::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'membership_number' => 'MEM-' . fake()->unique()->numberBetween(100000, 999999),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'status' => CustomerMembership::STATUS_ACTIVE,
            'price_paid' => 12000,
            'auto_renew' => $autoRenew,
        ]);
    }

    private function paidInvoice($tenant, $branch, Customer $customer, float $amount, bool $paid = true): Invoice
    {
        $invoice = Invoice::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-' . fake()->unique()->numberBetween(100000, 999999),
            'subtotal' => $amount,
            'total' => $amount,
            'paid_amount' => $paid ? $amount : 0,
            'balance_amount' => $paid ? 0 : $amount,
            'status' => Invoice::STATUS_ISSUED,
            'payment_status' => $paid ? Invoice::PAYMENT_PAID : Invoice::PAYMENT_UNPAID,
            'issued_at' => now(),
            'paid_at' => $paid ? now() : null,
        ]);

        InvoiceItem::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'sale_id' => $invoice->id,
            'item_type' => 'service',
            'item_id' => 0,
            'item_name' => 'Hair Cut',
            'quantity' => 1,
            'unit_price' => $amount,
            'gross_amount' => $amount,
            'net_amount' => $amount,
            'total_amount' => $amount,
            'total' => $amount,
        ]);

        return $invoice->fresh(['tenant', 'customer', 'items']);
    }
}
