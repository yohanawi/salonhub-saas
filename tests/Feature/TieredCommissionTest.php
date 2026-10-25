<?php

namespace Tests\Feature;

use App\Models\CommissionRule;
use App\Models\CommissionSetting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Staff;
use App\Models\StaffCommission;
use App\Models\User;
use App\Services\Commission\CommissionGenerator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Concerns\BuildsSalonFixtures;
use Tests\TestCase;

class TieredCommissionTest extends TestCase
{
    use BuildsSalonFixtures;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_owner_creates_tiered_rule_from_the_form(): void
    {
        [$tenant, $owner] = $this->makeSalon();

        $this->actingAs($owner)->post(route('commission-management.rules.store'), [
            'commission_scope' => CommissionRule::SCOPE_TENANT,
            'commission_type' => CommissionRule::TYPE_TIERED,
            'calculate_on' => CommissionSetting::BASIS_NET_AFTER_DISCOUNT,
            'is_active' => 1,
            'tiers' => [
                ['min_sales' => 100000, 'rate' => 15],
                ['min_sales' => 50000, 'rate' => 12],
            ],
        ])->assertRedirect(route('commission-management.rules.index'));

        $rule = CommissionRule::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->assertTrue($rule->isTiered());
        // Sorted, with an explicit 0% starting tier added.
        $this->assertSame([
            ['min_sales' => 0, 'rate' => 0],
            ['min_sales' => 50000, 'rate' => 12],
            ['min_sales' => 100000, 'rate' => 15],
        ], $rule->tiers);

        $this->actingAs($owner)->get(route('commission-management.rules.index'))->assertOk()->assertSee('Tiered');

        $this->actingAs($owner)->post(route('commission-management.rules.store'), [
            'commission_scope' => CommissionRule::SCOPE_TENANT,
            'commission_type' => CommissionRule::TYPE_TIERED,
            'calculate_on' => CommissionSetting::BASIS_NET_AFTER_DISCOUNT,
        ])->assertSessionHasErrors('tiers');
    }

    public function test_rate_follows_the_tier_reached_by_monthly_sales(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        $staff = $this->makeStaff($tenant, $branch);
        $customer = $this->makeCustomer($tenant, $branch);
        $service = $this->makeService($tenant, $branch);
        $this->enableCommissions($tenant);

        CommissionRule::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'commission_scope' => CommissionRule::SCOPE_TENANT,
            'commission_type' => CommissionRule::TYPE_TIERED,
            'commission_value' => 0,
            'tiers' => [['min_sales' => 0, 'rate' => 10], ['min_sales' => 100000, 'rate' => 15]],
            'calculate_on' => CommissionSetting::BASIS_NET_AFTER_DISCOUNT,
            'priority' => 100,
            'is_active' => true,
        ]);

        $generator = app(CommissionGenerator::class);

        // 60k: still in the first tier.
        $first = $this->paidInvoice($tenant, $branch, $customer, $staff, $service, 60000, now()->startOfMonth()->addDay());
        $generator->generateForInvoice($first, $owner);
        // +50k reaches 110k in the month: this sale earns the higher rate.
        $second = $this->paidInvoice($tenant, $branch, $customer, $staff, $service, 50000, now()->startOfMonth()->addDays(2));
        $generator->generateForInvoice($second, $owner);
        // A sale in the previous month never counts towards this month's target.
        $old = $this->paidInvoice($tenant, $branch, $customer, $staff, $service, 500000, now()->startOfMonth()->subDays(3));

        $commissions = StaffCommission::withoutTenantScope()->where('staff_id', $staff->id)->orderBy('id')->get();

        $this->assertCount(2, $commissions);
        $this->assertEquals('6000.00', $commissions[0]->commission_amount);
        $this->assertEquals('10.00', $commissions[0]->commission_rate);
        $this->assertEquals('7500.00', $commissions[1]->commission_amount);
        $this->assertEquals('15.00', $commissions[1]->commission_rate);
        $this->assertSame(CommissionRule::TYPE_TIERED, $commissions[1]->commission_type);
        $this->assertStringContainsString('Tier 2', $commissions[1]->notes);
        $this->assertNotNull($old);
    }

    public function test_staff_sees_live_commission_dashboard_with_tier_progress(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'email_verified_at' => now()]);
        $user->assignRole('Beautician');
        $staff = $this->makeStaff($tenant, $branch, $user);
        $customer = $this->makeCustomer($tenant, $branch);
        $service = $this->makeService($tenant, $branch);
        $this->enableCommissions($tenant);

        CommissionRule::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'staff_id' => $staff->id,
            'commission_scope' => CommissionRule::SCOPE_STAFF,
            'commission_type' => CommissionRule::TYPE_TIERED,
            'commission_value' => 0,
            'tiers' => [['min_sales' => 0, 'rate' => 10], ['min_sales' => 100000, 'rate' => 15]],
            'calculate_on' => CommissionSetting::BASIS_NET_AFTER_DISCOUNT,
            'priority' => 30,
            'is_active' => true,
        ]);

        $invoice = $this->paidInvoice($tenant, $branch, $customer, $staff, $service, 40000, now());
        app(CommissionGenerator::class)->generateForInvoice($invoice, $owner);

        $response = $this->actingAs($user)->get(route('profile.commissions'))
            ->assertOk()
            ->assertSee('Sales Target')
            ->assertSee('Your personal target')
            ->assertSee('LKR 60,000.00 more to reach', false)
            ->assertSee($invoice->invoice_number);

        $this->assertEquals(4000.0, $response->viewData('totals')['net']);
        $this->assertEquals(40000.0, $response->viewData('sales'));

        $this->actingAs($user)->get(route('profile.show'))->assertOk()->assertSee('My Commissions');

        // Someone without commission access is refused.
        $outsider = User::factory()->create(['tenant_id' => $tenant->id, 'email_verified_at' => now()]);
        $this->actingAs($outsider)->get(route('profile.commissions'))->assertForbidden();
    }

    private function enableCommissions($tenant): void
    {
        CommissionSetting::withoutTenantScope()->updateOrCreate(['tenant_id' => $tenant->id], [
            'commission_enabled' => true,
            'default_service_commission_type' => CommissionSetting::TYPE_NONE,
            'default_service_commission_value' => 0,
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

    private function paidInvoice($tenant, $branch, $customer, Staff $staff, $service, float $amount, $paidAt): Invoice
    {
        $invoice = Invoice::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-' . fake()->unique()->numberBetween(100000, 999999),
            'subtotal' => $amount,
            'total' => $amount,
            'paid_amount' => $amount,
            'status' => 'issued',
            'payment_status' => Invoice::PAYMENT_PAID,
            'issued_at' => $paidAt,
            'paid_at' => $paidAt,
        ]);

        InvoiceItem::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'sale_id' => $invoice->id,
            'item_type' => 'service',
            'item_id' => $service->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'item_name' => $service->name,
            'quantity' => 1,
            'unit_price' => $amount,
            'gross_amount' => $amount,
            'discount_amount' => 0,
            'net_amount' => $amount,
            'total_amount' => $amount,
            'total' => $amount,
        ]);

        return $invoice->fresh(['items.staff', 'items.service', 'items.product']);
    }
}
