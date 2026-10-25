<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\CustomerLoyaltyAccount;
use App\Models\CustomerNote;
use App\Models\LoyaltyPointTransaction;
use App\Models\Promotion;
use App\Models\Role;
use App\Models\User;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Settings\SettingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsSalonFixtures;
use Tests\TestCase;

class CustomerMergeTest extends TestCase
{
    use BuildsSalonFixtures;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_creating_a_customer_with_a_known_phone_warns_until_confirmed(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        $existing = $this->makeCustomer($tenant, $branch); // 0771234567

        $payload = [
            'branch_id' => $branch->id,
            'first_name' => 'Emma',
            'last_name' => 'S',
            'phone' => '+94 77 123 4567',
            'status' => Customer::STATUS_ACTIVE,
        ];

        $this->actingAs($owner)
            ->post(route('customer-management.customers.store'), $payload)
            ->assertSessionHasErrors(['phone' => "Emma Silva ({$existing->customer_code}) already has this phone."])
            ->assertSessionHas('duplicate_customer_id', $existing->id);

        $this->assertSame(1, Customer::query()->where('tenant_id', $tenant->id)->count());

        $this->actingAs($owner)
            ->post(route('customer-management.customers.store'), $payload + ['allow_duplicate' => 1])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Customer::query()->where('tenant_id', $tenant->id)->count());
    }

    public function test_detection_follows_the_salon_setting(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        $existing = $this->makeCustomer($tenant, $branch);
        $existing->update(['email' => 'emma@example.com']);
        app(SettingService::class)->setMany($tenant, null, ['customer.duplicate_detection' => 'email'], $owner);

        // Same phone is fine when only email is checked...
        $this->actingAs($owner)
            ->post(route('customer-management.customers.store'), [
                'first_name' => 'Kamal', 'phone' => '0771234567', 'email' => 'kamal@example.com', 'status' => 'active',
            ])
            ->assertSessionHasNoErrors();

        // ...but the same email (any case) is caught.
        $this->actingAs($owner)
            ->post(route('customer-management.customers.store'), [
                'first_name' => 'Emma', 'phone' => '0711111111', 'email' => 'EMMA@example.com', 'status' => 'active',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_editing_a_customer_does_not_flag_itself(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        $customer = $this->makeCustomer($tenant, $branch);

        $this->actingAs($owner)
            ->put(route('customer-management.customers.update', $customer), [
                'first_name' => 'Emma', 'last_name' => 'Perera', 'phone' => $customer->phone, 'status' => 'active',
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_merge_moves_history_and_combines_loyalty_points(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon(['loyalty_membership']);
        $keep = $this->makeCustomer($tenant, $branch);
        $keep->update(['email' => null, 'notes' => 'Prefers mornings.']);
        $duplicate = $this->makeCustomer($tenant, $branch);
        $duplicate->update(['email' => 'emma@example.com', 'date_of_birth' => '1990-05-01', 'notes' => 'Allergic to latex.']);

        $appointment = Appointment::withoutTenantScope()->create([
            'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'customer_id' => $duplicate->id,
            'appointment_number' => 'APT-MERGE-1', 'status' => Appointment::STATUS_COMPLETED,
            'starts_at' => now()->subWeek(), 'ends_at' => now()->subWeek()->addHour(),
        ]);
        CustomerNote::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'customer_id' => $duplicate->id, 'note' => 'Called about colour.']);

        $program = app(LoyaltyService::class)->ensureDefaultProgram($tenant, $owner);
        $keepAccount = $this->loyaltyAccount($keep, $program->id, 100);
        $duplicateAccount = $this->loyaltyAccount($duplicate, $program->id, 40);
        LoyaltyPointTransaction::withoutTenantScope()->create([
            'tenant_id' => $tenant->id, 'customer_id' => $duplicate->id, 'loyalty_account_id' => $duplicateAccount->id,
            'type' => 'earn', 'points' => 40, 'balance_before' => 0, 'balance_after' => 40,
        ]);

        $promotion = Promotion::withoutTenantScope()->create([
            'tenant_id' => $tenant->id, 'name' => 'VIP', 'promotion_type' => Promotion::TYPE_STANDARD,
            'discount_type' => Promotion::DISCOUNT_PERCENTAGE, 'discount_value' => 10,
            'application_type' => Promotion::APPLICATION_AUTOMATIC, 'starts_at' => now()->subDay(),
            'customer_scope' => Promotion::CUSTOMER_ALL, 'status' => Promotion::STATUS_ACTIVE,
        ]);
        foreach ([$keep, $duplicate] as $customer) {
            DB::table('promotion_customers')->insert(['tenant_id' => $tenant->id, 'promotion_id' => $promotion->id, 'customer_id' => $customer->id]);
        }

        $this->actingAs($owner)
            ->get(route('customer-management.customers.show', $keep))
            ->assertOk()
            ->assertSee('Possible duplicate customer')
            ->assertSee($duplicate->customer_code);

        $this->actingAs($owner)
            ->post(route('customer-management.customers.merge', $keep), ['duplicate_id' => $duplicate->id])
            ->assertSessionHas('status');

        $this->assertSame($keep->id, (int) $appointment->fresh()->customer_id);
        $this->assertSame(1, CustomerNote::withoutTenantScope()->where('customer_id', $keep->id)->count());
        $this->assertSame(0, CustomerNote::withoutTenantScope()->where('customer_id', $duplicate->id)->count());

        $keepAccount->refresh();
        $this->assertSame(140, (int) $keepAccount->available_points);
        $this->assertSame(140, (int) $keepAccount->lifetime_earned_points);
        $this->assertNull(CustomerLoyaltyAccount::withoutTenantScope()->find($duplicateAccount->id));
        $this->assertSame($keepAccount->id, (int) LoyaltyPointTransaction::withoutTenantScope()->where('customer_id', $keep->id)->firstOrFail()->loyalty_account_id);

        $this->assertSame(1, DB::table('promotion_customers')->where('promotion_id', $promotion->id)->count());

        $keep->refresh();
        $this->assertSame('emma@example.com', $keep->email);
        $this->assertSame('1990-05-01', $keep->date_of_birth->toDateString());
        $this->assertSame("Prefers mornings.\nAllergic to latex.", $keep->notes);

        $archived = Customer::withoutTenantScope()->withTrashed()->findOrFail($duplicate->id);
        $this->assertTrue($archived->trashed());
        $this->assertSame($keep->id, (int) $archived->merged_into_customer_id);
        $this->assertSame(Customer::STATUS_INACTIVE, $archived->status);
    }

    public function test_merge_needs_permission_and_the_same_salon(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        $keep = $this->makeCustomer($tenant, $branch);
        [$otherTenant, , $otherBranch] = $this->makeSalon();
        $foreign = $this->makeCustomer($otherTenant, $otherBranch);

        $this->actingAs($owner)
            ->post(route('customer-management.customers.merge', $keep), ['duplicate_id' => $foreign->id])
            ->assertSessionHasErrors('duplicate_id');
        $this->assertFalse($foreign->fresh()->trashed());

        $receptionist = User::factory()->create(['tenant_id' => $tenant->id, 'email_verified_at' => now()]);
        $receptionist->assignRole(Role::firstOrCreate(['name' => 'Receptionist', 'guard_name' => 'web'])->givePermissionTo(['customer.view', 'customer.update']));

        $this->actingAs($receptionist)
            ->post(route('customer-management.customers.merge', $keep), ['duplicate_id' => $this->makeCustomer($tenant, $branch)->id])
            ->assertForbidden();
    }

    private function loyaltyAccount(Customer $customer, int $programId, int $points): CustomerLoyaltyAccount
    {
        return CustomerLoyaltyAccount::withoutTenantScope()->create([
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
            'loyalty_program_id' => $programId,
            'available_points' => $points,
            'lifetime_earned_points' => $points,
            'status' => 'active',
            'joined_at' => now(),
        ]);
    }
}
