<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentDeposit;
use App\Models\AppointmentService;
use App\Models\Branch;
use App\Models\CashRegisterMovement;
use App\Models\Customer;
use App\Models\CustomerPackage;
use App\Models\GiftCard;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\Staff;
use App\Models\StaffCommission;
use App\Models\StaffSalaryStructure;
use App\Models\StaffTip;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Billing\CashRegisterService;
use App\Services\Payroll\PayrollPaymentService;
use App\Services\Payroll\PayrollRunService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PosPhaseTwoTest extends TestCase
{
    use DatabaseTransactions;

    private Tenant $tenant;
    private User $owner;
    private Branch $branch;
    private Service $service;
    private Staff $stylist;
    private Customer $customer;
    private PaymentMethod $cash;
    private PaymentMethod $card;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (RolesPermissionsSeeder::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'Salon Owner', 'guard_name' => 'web'])
            ->givePermissionTo(RolesPermissionsSeeder::PERMISSIONS);

        $this->salonSetup();
    }

    // ---------------------------------------------------------------- Tips

    public function test_tip_is_collected_with_the_sale_but_kept_out_of_the_total(): void
    {
        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'tips' => [['staff_id' => $this->stylist->id, 'amount' => 500]],
                'amount' => 3500,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $invoice = $this->invoice();

        $this->assertEquals('3000.00', $invoice->total);
        $this->assertEquals('500.00', $invoice->tip_amount);
        $this->assertEquals('3500.00', $invoice->paid_amount);
        $this->assertEquals('0.00', $invoice->balance_amount);
        $this->assertSame(Invoice::PAYMENT_PAID, $invoice->payment_status);

        $tip = StaffTip::withoutTenantScope()->where('invoice_id', $invoice->id)->firstOrFail();
        $this->assertSame($this->stylist->id, $tip->staff_id);
        $this->assertSame(StaffTip::STATUS_EARNED, $tip->status);

        // Commission is still worked out on the 3000 service value only.
        $this->assertEquals(300.0, (float) StaffCommission::withoutTenantScope()->where('invoice_id', $invoice->id)->sum('commission_amount'));
    }

    public function test_tip_stays_pending_until_the_invoice_is_fully_paid(): void
    {
        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'tips' => [['staff_id' => $this->stylist->id, 'amount' => 500]],
                'amount' => 3000,
            ]))
            ->assertSessionHasNoErrors();

        $invoice = $this->invoice();
        $this->assertEquals('500.00', $invoice->balance_amount);
        $this->assertSame(StaffTip::STATUS_PENDING, StaffTip::withoutTenantScope()->where('invoice_id', $invoice->id)->value('status'));

        $this->actingAs($this->owner)
            ->post(route('billing.invoices.payments.store', $invoice), ['payment_method_id' => $this->cash->id, 'amount' => 500])
            ->assertSessionHasNoErrors();

        $this->assertSame(Invoice::PAYMENT_PAID, $invoice->fresh()->payment_status);
        $this->assertSame(StaffTip::STATUS_EARNED, StaffTip::withoutTenantScope()->where('invoice_id', $invoice->id)->value('status'));
    }

    public function test_tip_for_staff_outside_the_branch_is_rejected(): void
    {
        $outsider = Staff::create([
            'tenant_id' => $this->tenant->id,
            'employee_code' => 'STF-0099',
            'first_name' => 'Outside',
            'last_name' => 'Staff',
            'job_title' => 'Stylist',
            'status' => Staff::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'tips' => [['staff_id' => $outsider->id, 'amount' => 500]],
                'amount' => 3500,
            ]))
            ->assertSessionHasErrors('tips.0.staff_id');

        $this->assertSame(0, Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->count());
    }

    public function test_earned_tips_are_paid_through_payroll(): void
    {
        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'tips' => [['staff_id' => $this->stylist->id, 'amount' => 750]],
                'amount' => 3750,
            ]))
            ->assertSessionHasNoErrors();

        StaffSalaryStructure::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $this->stylist->id,
            'salary_type' => StaffSalaryStructure::TYPE_SALARY_COMMISSION,
            'basic_salary' => 50000,
            'hourly_rate' => 0,
            'daily_rate' => 0,
            'commission_enabled' => false,
            'payroll_frequency' => 'monthly',
            'effective_from' => now()->startOfMonth()->toDateString(),
            'status' => StaffSalaryStructure::STATUS_ACTIVE,
            'created_by' => $this->owner->id,
            'updated_by' => $this->owner->id,
        ]);

        $period = PayrollPeriod::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'name' => 'This month',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => PayrollPeriod::STATUS_OPEN,
            'created_by' => $this->owner->id,
        ]);

        $run = app(PayrollRunService::class)->generate($period, $this->owner);
        $item = $run->items->firstWhere('staff_id', $this->stylist->id);

        $this->assertEquals('750.00', $item->tip_amount);
        $this->assertEquals('50750.00', $item->gross_pay);
        $this->assertTrue($item->lines->contains(fn ($line) => $line->category === 'staff_tip' && (float) $line->amount === 750.0));

        $run->update(['status' => PayrollRun::STATUS_APPROVED]);
        app(PayrollPaymentService::class)->payRun($run, ['payment_method' => 'cash'], $this->owner);

        $tip = StaffTip::withoutTenantScope()->where('staff_id', $this->stylist->id)->firstOrFail();
        $this->assertSame(StaffTip::STATUS_PAID, $tip->status);
        $this->assertNotNull($tip->paid_at);
    }

    public function test_voiding_the_invoice_voids_its_tips(): void
    {
        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'tips' => [['staff_id' => $this->stylist->id, 'amount' => 500]],
                'amount' => 3500,
            ]))
            ->assertSessionHasNoErrors();

        $invoice = $this->invoice();

        $this->actingAs($this->owner)
            ->post(route('billing.invoices.void', $invoice), ['void_reason' => 'Wrong customer'])
            ->assertSessionHasNoErrors();

        $this->assertSame(StaffTip::STATUS_VOID, StaffTip::withoutTenantScope()->where('invoice_id', $invoice->id)->value('status'));
    }

    // ---------------------------------------------------------------- Gift cards

    public function test_gift_card_is_sold_as_an_invoice_and_activated(): void
    {
        $this->actingAs($this->owner)
            ->post(route('billing.gift-cards.store'), [
                'amount' => 5000,
                'code' => 'avurudu-2026',
                'recipient_name' => 'Amaya',
                'payments' => [['payment_method_id' => $this->cash->id, 'amount' => 5000]],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $card = GiftCard::withoutTenantScope()->where('tenant_id', $this->tenant->id)->firstOrFail();

        $this->assertSame('AVURUDU-2026', $card->code);
        $this->assertEquals('5000.00', $card->balance);
        $this->assertSame(GiftCard::STATUS_ACTIVE, $card->status);
        $this->assertSame(Invoice::PAYMENT_PAID, $card->invoice->payment_status);
        $this->assertDatabaseHas('sale_items', ['sale_id' => $card->invoice_id, 'item_type' => 'gift_card', 'tax_amount' => 0]);
        $this->assertDatabaseHas('gift_card_transactions', ['gift_card_id' => $card->id, 'type' => 'issue', 'amount' => 5000]);

        $this->actingAs($this->owner)
            ->get(route('billing.gift-cards.show', $card))
            ->assertOk()
            ->assertSee('AVURUDU-2026');
    }

    public function test_gift_card_pays_for_a_sale_and_its_balance_goes_down(): void
    {
        $card = $this->giftCard(5000);

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'payments' => [['payment_method_id' => $this->giftMethod()->id, 'amount' => 3000, 'gift_card_code' => 'gc-test-0001']],
            ]))
            ->assertSessionHasNoErrors();

        $invoice = $this->invoice();

        $this->assertSame(Invoice::PAYMENT_PAID, $invoice->payment_status);
        $this->assertEquals('2000.00', $card->fresh()->balance);
        $this->assertDatabaseHas('payments', ['sale_id' => $invoice->id, 'gift_card_id' => $card->id, 'amount' => 3000]);
        $this->assertDatabaseHas('gift_card_transactions', ['gift_card_id' => $card->id, 'type' => 'redeem', 'amount' => -3000, 'balance_after' => 2000]);
    }

    public function test_gift_card_with_too_little_balance_is_rejected(): void
    {
        $this->giftCard(1000);

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'payments' => [['payment_method_id' => $this->giftMethod()->id, 'amount' => 3000, 'gift_card_code' => 'GC-TEST-0001']],
            ]))
            ->assertSessionHasErrors(['payments.0.gift_card_code' => 'This gift card only has LKR 1,000.00 left.']);

        $this->assertSame(0, Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->count());
    }

    public function test_expired_or_unknown_gift_card_is_rejected(): void
    {
        $this->giftCard(5000, ['expires_at' => now()->subDay()->toDateString()]);

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'payments' => [['payment_method_id' => $this->giftMethod()->id, 'amount' => 3000, 'gift_card_code' => 'GC-TEST-0001']],
            ]))
            ->assertSessionHasErrors('payments.0.gift_card_code');

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'payments' => [['payment_method_id' => $this->giftMethod()->id, 'amount' => 3000, 'gift_card_code' => 'NOPE']],
            ]))
            ->assertSessionHasErrors(['payments.0.gift_card_code' => 'Gift card not found. Check the code and try again.']);
    }

    public function test_split_payment_with_gift_card_and_cash(): void
    {
        $card = $this->giftCard(1000);

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'payments' => [
                    ['payment_method_id' => $this->giftMethod()->id, 'amount' => 1000, 'gift_card_code' => 'GC-TEST-0001'],
                    ['payment_method_id' => $this->cash->id, 'amount' => 2000],
                ],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(Invoice::PAYMENT_PAID, $this->invoice()->payment_status);
        $this->assertEquals('0.00', $card->fresh()->balance);
        $this->assertSame(GiftCard::STATUS_REDEEMED, $card->fresh()->status);
    }

    public function test_voiding_an_invoice_gives_the_gift_card_balance_back(): void
    {
        $card = $this->giftCard(5000);

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'payments' => [['payment_method_id' => $this->giftMethod()->id, 'amount' => 3000, 'gift_card_code' => 'GC-TEST-0001']],
            ]))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->owner)
            ->post(route('billing.invoices.void', $this->invoice()), ['void_reason' => 'Mistake'])
            ->assertSessionHasNoErrors();

        $this->assertEquals('5000.00', $card->fresh()->balance);
        $this->assertDatabaseHas('gift_card_transactions', ['gift_card_id' => $card->id, 'type' => 'reverse', 'amount' => 3000]);
    }

    public function test_sale_of_a_used_gift_card_cannot_be_voided(): void
    {
        $this->actingAs($this->owner)
            ->post(route('billing.gift-cards.store'), [
                'amount' => 5000,
                'code' => 'GC-SOLD-0001',
                'payments' => [['payment_method_id' => $this->cash->id, 'amount' => 5000]],
            ])
            ->assertSessionHasNoErrors();

        $card = GiftCard::withoutTenantScope()->where('code', 'GC-SOLD-0001')->firstOrFail();

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'payments' => [['payment_method_id' => $this->giftMethod()->id, 'amount' => 3000, 'gift_card_code' => 'GC-SOLD-0001']],
            ]))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->owner)
            ->post(route('billing.invoices.void', $card->invoice), ['void_reason' => 'Changed mind'])
            ->assertSessionHasErrors('invoice');

        $this->assertSame(Invoice::STATUS_ISSUED, $card->invoice->fresh()->status);
    }

    public function test_gift_card_cannot_buy_another_gift_card(): void
    {
        $this->giftCard(10000);

        $this->actingAs($this->owner)
            ->post(route('billing.gift-cards.store'), [
                'amount' => 5000,
                'payments' => [['payment_method_id' => $this->giftMethod()->id, 'amount' => 5000, 'gift_card_code' => 'GC-TEST-0001']],
            ])
            ->assertSessionHasErrors();

        $this->assertSame(1, GiftCard::withoutTenantScope()->where('tenant_id', $this->tenant->id)->count());
    }

    public function test_gift_card_lookup_reports_the_balance(): void
    {
        $this->giftCard(4200);

        $this->actingAs($this->owner)
            ->getJson(route('billing.gift-cards.lookup', ['code' => 'gc-test-0001']))
            ->assertOk()
            ->assertJson(['found' => true, 'usable' => true, 'balance' => 4200]);
    }

    public function test_gift_card_method_is_not_offered_for_expenses_or_refunds(): void
    {
        $this->giftMethod();

        $this->assertFalse(PaymentMethod::withoutTenantScope()->where('tenant_id', $this->tenant->id)->manual()->where('type', PaymentMethod::TYPE_GIFT_CARD)->exists());
        $this->assertTrue(PaymentMethod::withoutTenantScope()->where('tenant_id', $this->tenant->id)->tenderable()->where('type', PaymentMethod::TYPE_GIFT_CARD)->exists());
        $this->assertFalse(PaymentMethod::withoutTenantScope()->where('tenant_id', $this->tenant->id)->tenderable()->where('type', PaymentMethod::TYPE_DEPOSIT)->exists());
    }

    // ---------------------------------------------------------------- Deposits

    public function test_cash_deposit_goes_into_the_drawer_and_is_applied_at_checkout(): void
    {
        $shift = app(CashRegisterService::class)->open($this->branch, 0, null, $this->owner);
        $appointment = $this->appointment(Appointment::STATUS_CONFIRMED);

        $this->actingAs($this->owner)
            ->post(route('billing.appointments.deposits.store', $appointment), ['amount' => 1000, 'payment_method_id' => $this->cash->id])
            ->assertSessionHasNoErrors();

        $deposit = AppointmentDeposit::withoutTenantScope()->where('appointment_id', $appointment->id)->firstOrFail();
        $this->assertSame(AppointmentDeposit::STATUS_HELD, $deposit->status);
        $this->assertTrue(CashRegisterMovement::withoutTenantScope()->where('cash_register_shift_id', $shift->id)->where('type', CashRegisterMovement::TYPE_CASH_IN)->where('amount', 1000)->exists());

        $appointment->update(['status' => Appointment::STATUS_COMPLETED, 'completed_at' => now()]);

        $this->actingAs($this->owner)
            ->get(route('billing.checkout.appointments.create', $appointment))
            ->assertOk()
            ->assertSee('Deposit Paid');

        $this->actingAs($this->owner)
            ->post(route('billing.checkout.appointments.store', $appointment), [
                'payments' => [['payment_method_id' => $this->card->id, 'amount' => 2000, 'transaction_reference' => 'CARD-1']],
            ])
            ->assertSessionHasNoErrors();

        $invoice = Invoice::withoutTenantScope()->where('appointment_id', $appointment->id)->firstOrFail();

        $this->assertSame(Invoice::PAYMENT_PAID, $invoice->payment_status);
        $this->assertEquals('3000.00', $invoice->paid_amount);
        $this->assertTrue(Payment::withoutTenantScope()->where('sale_id', $invoice->id)->where('appointment_deposit_id', $deposit->id)->where('amount', 1000)->exists());
        $this->assertSame(AppointmentDeposit::STATUS_APPLIED, $deposit->fresh()->status);
        $this->assertSame($invoice->id, $deposit->fresh()->applied_invoice_id);
    }

    public function test_deposit_covering_the_whole_bill_needs_no_other_payment(): void
    {
        $appointment = $this->appointment(Appointment::STATUS_CONFIRMED);

        $this->actingAs($this->owner)
            ->post(route('billing.appointments.deposits.store', $appointment), ['amount' => 3000, 'payment_method_id' => $this->card->id, 'transaction_reference' => 'BANK-9'])
            ->assertSessionHasNoErrors();

        $appointment->update(['status' => Appointment::STATUS_COMPLETED, 'completed_at' => now()]);

        $this->actingAs($this->owner)
            ->post(route('billing.checkout.appointments.store', $appointment), [])
            ->assertSessionHasNoErrors();

        $invoice = Invoice::withoutTenantScope()->where('appointment_id', $appointment->id)->firstOrFail();
        $this->assertSame(Invoice::PAYMENT_PAID, $invoice->payment_status);
    }

    public function test_checkout_without_deposit_still_requires_a_payment(): void
    {
        $appointment = $this->appointment(Appointment::STATUS_COMPLETED);

        $this->actingAs($this->owner)
            ->post(route('billing.checkout.appointments.store', $appointment), [])
            ->assertSessionHasErrors('payments');

        $this->assertFalse(Invoice::withoutTenantScope()->where('appointment_id', $appointment->id)->exists());
    }

    public function test_held_deposit_can_be_refunded_or_forfeited(): void
    {
        $appointment = $this->appointment(Appointment::STATUS_CONFIRMED);

        foreach ([500, 700] as $amount) {
            $this->actingAs($this->owner)
                ->post(route('billing.appointments.deposits.store', $appointment), ['amount' => $amount, 'payment_method_id' => $this->card->id, 'transaction_reference' => 'REF'])
                ->assertSessionHasNoErrors();
        }

        [$first, $second] = AppointmentDeposit::withoutTenantScope()->where('appointment_id', $appointment->id)->orderBy('id')->get()->all();

        $this->actingAs($this->owner)
            ->post(route('billing.deposits.refund', $first), ['reason' => 'Customer cancelled early'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->owner)
            ->post(route('billing.deposits.forfeit', $second), ['reason' => 'No-show'])
            ->assertSessionHasNoErrors();

        $this->assertSame(AppointmentDeposit::STATUS_REFUNDED, $first->fresh()->status);
        $this->assertEquals('500.00', $first->fresh()->refunded_amount);
        $this->assertSame(AppointmentDeposit::STATUS_FORFEITED, $second->fresh()->status);

        $this->actingAs($this->owner)
            ->post(route('billing.deposits.refund', $second), ['reason' => 'Again'])
            ->assertSessionHasErrors('deposit');
    }

    public function test_deposit_method_cannot_be_tendered_by_hand(): void
    {
        $this->giftMethod();
        $deposit = PaymentMethod::withoutTenantScope()->where('tenant_id', $this->tenant->id)->where('type', PaymentMethod::TYPE_DEPOSIT)->firstOrFail();

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload(['payment_method_id' => $deposit->id]))
            ->assertSessionHasErrors('payments.0.payment_method_id');
    }

    public function test_appointment_page_shows_the_deposit_card(): void
    {
        $appointment = $this->appointment(Appointment::STATUS_CONFIRMED);

        $this->actingAs($this->owner)
            ->get(route('appointment-management.appointments.show', $appointment))
            ->assertOk()
            ->assertSee('Take Deposit');
    }

    // ---------------------------------------------------------------- Packages

    public function test_package_is_created_and_sold_to_a_customer(): void
    {
        $this->actingAs($this->owner)
            ->post(route('billing.service-packages.store'), [
                'name' => 'Hair Cut x5',
                'price' => 12000,
                'validity_days' => 180,
                'is_active' => 1,
                'items' => [['service_id' => $this->service->id, 'quantity' => 5]],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('billing.service-packages.index'));

        $package = ServicePackage::withoutTenantScope()->where('tenant_id', $this->tenant->id)->firstOrFail();

        $this->actingAs($this->owner)
            ->post(route('billing.customer-packages.store'), [
                'customer_id' => $this->customer->id,
                'service_package_id' => $package->id,
                'payments' => [['payment_method_id' => $this->cash->id, 'amount' => 12000]],
            ])
            ->assertSessionHasNoErrors();

        $sold = CustomerPackage::withoutTenantScope()->where('customer_id', $this->customer->id)->firstOrFail();

        $this->assertSame(CustomerPackage::STATUS_ACTIVE, $sold->status);
        $this->assertSame(now()->addDays(180)->toDateString(), $sold->expires_at->toDateString());
        $this->assertSame(5, $sold->items->first()->total_quantity);
        $this->assertSame(Invoice::PAYMENT_PAID, $sold->invoice->payment_status);
        $this->assertDatabaseHas('sale_items', ['sale_id' => $sold->invoice_id, 'item_type' => 'package']);

        $this->actingAs($this->owner)->get(route('billing.customer-packages.show', $sold))->assertOk()->assertSee('Hair Cut x5');
        $this->actingAs($this->owner)->get(route('billing.service-packages.index'))->assertOk()->assertSee('Hair Cut x5');
    }

    public function test_package_credit_covers_the_service_at_checkout(): void
    {
        $sold = $this->soldPackage(2);

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'customer_mode' => 'existing',
                'customer_id' => $this->customer->id,
                'payment_method_id' => null,
                'amount' => null,
            ]))
            ->assertSessionHasNoErrors();

        $invoice = $this->invoice();

        $this->assertEquals('0.00', $invoice->total);
        $this->assertEquals('3000.00', $invoice->package_discount_amount);
        $this->assertSame(Invoice::PAYMENT_PAID, $invoice->payment_status);
        $this->assertSame(1, $sold->items->first()->fresh()->used_quantity);
        $this->assertDatabaseHas('customer_package_usages', ['invoice_id' => $invoice->id, 'quantity' => 1]);
    }

    public function test_package_credits_can_be_skipped(): void
    {
        $sold = $this->soldPackage(2);

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'customer_mode' => 'existing',
                'customer_id' => $this->customer->id,
                'use_package_credits' => 0,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertEquals('3000.00', $this->invoice()->total);
        $this->assertSame(0, $sold->items->first()->fresh()->used_quantity);
    }

    public function test_last_credit_completes_the_package_and_void_gives_it_back(): void
    {
        $sold = $this->soldPackage(1);

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'customer_mode' => 'existing',
                'customer_id' => $this->customer->id,
                'service_items' => [['service_id' => $this->service->id, 'staff_id' => $this->stylist->id, 'quantity' => 2]],
                'amount' => 3000,
            ]))
            ->assertSessionHasNoErrors();

        $invoice = $this->invoice();

        // Two haircuts, one covered by the last credit.
        $this->assertEquals('3000.00', $invoice->total);
        $this->assertSame(CustomerPackage::STATUS_COMPLETED, $sold->fresh()->status);

        $this->actingAs($this->owner)
            ->post(route('billing.invoices.void', $invoice), ['void_reason' => 'Mistake'])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, $sold->items->first()->fresh()->used_quantity);
        $this->assertSame(CustomerPackage::STATUS_ACTIVE, $sold->fresh()->status);
    }

    public function test_expired_package_credits_are_not_used(): void
    {
        $sold = $this->soldPackage(3);
        $sold->update(['expires_at' => now()->subDay()->toDateString()]);

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'customer_mode' => 'existing',
                'customer_id' => $this->customer->id,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertEquals('3000.00', $this->invoice()->total);
    }

    // ---------------------------------------------------------------- Pages

    public function test_new_pos_pages_render(): void
    {
        $sold = $this->soldPackage(2);
        $card = $this->giftCard(2000);

        $this->actingAs($this->owner)
            ->post(route('billing.quick-sale.store'), $this->payload([
                'tips' => [['staff_id' => $this->stylist->id, 'amount' => 250]],
                'amount' => 3250,
            ]))
            ->assertSessionHasNoErrors();

        $invoice = $this->invoice();

        $this->actingAs($this->owner)->get(route('billing.quick-sale.create'))->assertOk()->assertSee('Add Tip')->assertSee('Use package credits')->assertSee('data-package-credits', false);
        $this->actingAs($this->owner)->get(route('billing.invoices.show', $invoice))->assertOk()->assertSee('Tips')->assertSee('250.00');
        $this->actingAs($this->owner)->get(route('billing.invoices.receipt', $invoice))->assertOk()->assertSee('Amount Due')->assertSee('3,250.00');
        $this->actingAs($this->owner)->get(route('billing.invoices.index'))->assertOk();
        $this->actingAs($this->owner)->get(route('billing.reports.day-end'))->assertOk()->assertSee('Tips collected for staff');
        $this->actingAs($this->owner)->get(route('billing.gift-cards.index'))->assertOk()->assertSee($card->code);
        $this->actingAs($this->owner)->get(route('billing.gift-cards.create'))->assertOk()->assertSee('Issue Gift Card');
        $this->actingAs($this->owner)->get(route('billing.service-packages.index'))->assertOk()->assertSee('Hair Cut Pack');
        $this->actingAs($this->owner)->get(route('billing.service-packages.create'))->assertOk();
        $this->actingAs($this->owner)->get(route('billing.service-packages.edit', $sold->package))->assertOk();
        $this->actingAs($this->owner)->get(route('billing.customer-packages.index'))->assertOk()->assertSee($sold->package_number);
        $this->actingAs($this->owner)->get(route('billing.customer-packages.create'))->assertOk()->assertSee('Sell Package');

        $appointment = $this->appointment(Appointment::STATUS_COMPLETED);

        $this->actingAs($this->owner)
            ->get(route('billing.checkout.appointments.create', $appointment))
            ->assertOk()
            ->assertSee('Add Tip')
            ->assertSee('Use package credits');
    }

    public function test_staff_without_permission_cannot_open_gift_cards_or_packages(): void
    {
        Role::firstOrCreate(['name' => 'Beautician', 'guard_name' => 'web'])->givePermissionTo(['appointments.view']);
        $beautician = User::factory()->create(['tenant_id' => $this->tenant->id, 'email_verified_at' => now()]);
        $beautician->assignRole('Beautician');

        $this->actingAs($beautician)->get(route('billing.gift-cards.index'))->assertForbidden();
        $this->actingAs($beautician)->get(route('billing.service-packages.create'))->assertForbidden();
        $this->actingAs($beautician)->post(route('billing.customer-packages.store'), [])->assertForbidden();
    }

    // ---------------------------------------------------------------- Helpers

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'customer_mode' => 'walk_in',
            'service_items' => [['service_id' => $this->service->id, 'staff_id' => $this->stylist->id, 'quantity' => 1]],
            'product_items' => [],
            'payment_method_id' => $this->cash->id,
            'amount' => 3000,
        ], $overrides);
    }

    private function invoice(): Invoice
    {
        return Invoice::withoutTenantScope()->where('tenant_id', $this->tenant->id)->latest('id')->firstOrFail();
    }

    private function giftMethod(): PaymentMethod
    {
        app(\App\Services\Billing\PaymentMethodService::class)->ensureDefaults($this->tenant);

        return PaymentMethod::withoutTenantScope()->where('tenant_id', $this->tenant->id)->where('type', PaymentMethod::TYPE_GIFT_CARD)->firstOrFail();
    }

    private function giftCard(float $balance, array $overrides = []): GiftCard
    {
        return GiftCard::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'code' => 'GC-TEST-0001',
            'initial_amount' => $balance,
            'balance' => $balance,
            'status' => GiftCard::STATUS_ACTIVE,
            'issued_by' => $this->owner->id,
        ], $overrides));
    }

    private function soldPackage(int $sessions): CustomerPackage
    {
        $package = ServicePackage::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Hair Cut Pack',
            'price' => 5000,
            'is_active' => true,
        ]);
        $package->items()->create(['tenant_id' => $this->tenant->id, 'service_id' => $this->service->id, 'quantity' => $sessions]);

        return app(\App\Services\Billing\PackageService::class)->sell($this->branch, $this->customer, $package, [
            'payments' => [['payment_method_id' => $this->cash->id, 'amount' => 5000]],
        ], $this->owner)->load('items');
    }

    private function appointment(string $status): Appointment
    {
        $appointment = Appointment::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->stylist->id,
            'appointment_number' => 'APT-' . fake()->unique()->numerify('######'),
            'booking_source' => 'reception',
            'source' => 'reception',
            'status' => $status,
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at' => now()->addDay()->setTime(11, 0),
            'subtotal' => 3000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 3000,
            'completed_at' => $status === Appointment::STATUS_COMPLETED ? now() : null,
            'created_by' => $this->owner->id,
            'updated_by' => $this->owner->id,
        ]);

        AppointmentService::create([
            'tenant_id' => $this->tenant->id,
            'appointment_id' => $appointment->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->stylist->id,
            'service_name' => 'Hair Cut',
            'duration_minutes' => 60,
            'price' => 3000,
            'unit_price' => 3000,
            'discount_amount' => 0,
            'total_price' => 3000,
            'starts_at' => $appointment->starts_at,
            'ends_at' => $appointment->ends_at,
            'status' => $status,
        ]);

        return $appointment->fresh(['appointmentServices']);
    }

    private function salonSetup(): void
    {
        $this->tenant = Tenant::create([
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
                'inventory' => true,
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

        $this->owner = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email_verified_at' => now(),
        ]);
        $this->owner->assignRole('Salon Owner');

        $this->branch = Branch::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Colombo',
            'code' => 'CMB',
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

        $this->service->branches()->attach($this->branch->id, [
            'tenant_id' => $this->tenant->id,
            'price' => 3000,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        $this->stylist = Staff::create([
            'tenant_id' => $this->tenant->id,
            'employee_code' => 'STF-0001',
            'first_name' => 'Nadeesha',
            'last_name' => 'Perera',
            'job_title' => 'Stylist',
            'status' => Staff::STATUS_ACTIVE,
            'is_bookable' => true,
            'commission_type' => 'percentage',
            'commission_value' => 10,
        ]);
        $this->stylist->branches()->attach($this->branch->id, ['tenant_id' => $this->tenant->id, 'is_primary' => true, 'status' => 'active']);
        $this->stylist->services()->attach($this->service->id, ['tenant_id' => $this->tenant->id, 'status' => 'active']);

        $this->customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUS-000001',
            'first_name' => 'Emma',
            'last_name' => 'Silva',
            'phone' => '0771234567',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $this->cash = PaymentMethod::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Cash',
            'code' => 'cash',
            'type' => PaymentMethod::TYPE_CASH,
            'is_active' => true,
        ]);

        $this->card = PaymentMethod::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Card',
            'code' => 'card',
            'type' => PaymentMethod::TYPE_CARD,
            'is_active' => true,
            'requires_reference' => true,
        ]);
    }
}
