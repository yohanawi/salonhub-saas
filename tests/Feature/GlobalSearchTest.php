<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
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

        Role::firstOrCreate(['name' => 'Branch Manager', 'guard_name' => 'web'])
            ->givePermissionTo(['customer.view', 'branches.view']);
    }

    public function test_owner_can_search_tenant_records_only(): void
    {
        [$tenant, $owner, $branch] = $this->tenantOwner('Glow Search Salon');
        [, , $otherBranch] = $this->tenantOwner('Other Search Salon');

        Customer::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'customer_code' => 'CUS-AMALI',
            'first_name' => 'Amali',
            'last_name' => 'Perera',
            'phone' => '0771111111',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        Customer::create([
            'tenant_id' => $otherBranch->tenant_id,
            'branch_id' => $otherBranch->id,
            'customer_code' => 'CUS-AMALI-OTHER',
            'first_name' => 'Amali',
            'last_name' => 'Silva',
            'phone' => '0772222222',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $payload = $this->actingAs($owner)
            ->getJson(route('global-search', ['q' => 'Amali']))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->json();

        $this->assertSame('Customers', $payload['groups'][0]['label']);
        $this->assertSame('Amali Perera', $payload['groups'][0]['items'][0]['title']);
        $this->assertStringContainsString(route('customer-management.customers.index'), $payload['groups'][0]['items'][0]['url']);
    }

    public function test_branch_manager_search_is_limited_to_assigned_branch(): void
    {
        [$tenant, , $branch] = $this->tenantOwner('Branch Search Salon');
        $otherBranch = $this->branch($tenant, 'Hidden Branch');
        $manager = User::factory()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'email_verified_at' => now(),
            'onboarded_at' => now(),
        ]);
        $manager->assignRole('Branch Manager');
        $manager->branches()->attach($branch->id, ['tenant_id' => $tenant->id]);

        Customer::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'customer_code' => 'CUS-NIMALI',
            'first_name' => 'Nimali',
            'last_name' => 'Assigned',
            'phone' => '0773333333',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        Customer::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $otherBranch->id,
            'customer_code' => 'CUS-NIMALI-HIDDEN',
            'first_name' => 'Nimali',
            'last_name' => 'Hidden',
            'phone' => '0774444444',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $payload = $this->actingAs($manager)
            ->getJson(route('global-search', ['q' => 'Nimali', 'type' => 'customers']))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->json();

        $this->assertSame('Nimali Assigned', $payload['groups'][0]['items'][0]['title']);
    }

    public function test_short_queries_return_empty_payload(): void
    {
        [, $owner] = $this->tenantOwner('Short Search Salon');

        $this->actingAs($owner)
            ->getJson(route('global-search', ['q' => 'A']))
            ->assertOk()
            ->assertJsonPath('total', 0)
            ->assertJsonPath('groups', []);
    }

    private function tenantOwner(string $name): array
    {
        $tenant = Tenant::create([
            'name' => $name,
            'slug' => str($name)->slug() . '-' . fake()->unique()->numberBetween(1000, 9999),
            'email' => fake()->unique()->safeEmail(),
            'status' => 'active',
            'timezone' => 'Asia/Colombo',
            'currency' => 'LKR',
        ]);

        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
            'onboarded_at' => now(),
        ]);
        $owner->assignRole('Salon Owner');

        $branch = $this->branch($tenant, 'Main Branch', true);

        return [$tenant, $owner, $branch];
    }

    private function branch(Tenant $tenant, string $name, bool $isMain = false): Branch
    {
        return Branch::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'code' => strtoupper(str($name)->substr(0, 3)) . '-' . fake()->unique()->numberBetween(1000, 9999),
            'status' => Branch::STATUS_ACTIVE,
            'is_active' => true,
            'is_main' => $isMain,
            'country' => 'LK',
            'currency' => 'LKR',
            'timezone' => 'Asia/Colombo',
        ]);
    }
}
