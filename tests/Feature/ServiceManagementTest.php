<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
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
            ->givePermissionTo(['service_categories.view', 'services.view']);

        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
    }

    public function test_super_admin_can_open_service_management_and_create_for_selected_tenant(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $branch = $this->branch($tenant, 'Colombo', 'CMB');
        $superAdmin = User::factory()->create([
            'tenant_id' => null,
            'email_verified_at' => now(),
        ]);
        $superAdmin->assignRole('Super Admin');

        $this->actingAs($superAdmin)
            ->get(route('services.index'))
            ->assertOk()
            ->assertSee('Services');

        $this->actingAs($superAdmin)
            ->get(route('service-categories.index'))
            ->assertOk()
            ->assertSee('Service Categories');

        $this->actingAs($superAdmin)
            ->post(route('service-categories.store'), [
                'tenant_id' => $tenant->id,
                'name' => 'Hair',
                'is_active' => 1,
            ])
            ->assertRedirect();

        $category = ServiceCategory::where('tenant_id', $tenant->id)->where('slug', 'hair')->firstOrFail();

        $this->actingAs($superAdmin)
            ->get(route('services.create', ['tenant_id' => $tenant->id]))
            ->assertOk()
            ->assertSee($tenant->name)
            ->assertSee($branch->name);

        $this->actingAs($superAdmin)
            ->post(route('services.store'), [
                'tenant_id' => $tenant->id,
                'name' => 'Admin Haircut',
                'category_id' => $category->id,
                'default_price' => 3200,
                'default_duration_minutes' => 45,
                'is_active' => 1,
                'branches' => [
                    $branch->id => [
                        'enabled' => 1,
                        'is_active' => 1,
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('services', [
            'tenant_id' => $tenant->id,
            'slug' => 'admin-haircut',
        ]);
    }

    public function test_owner_can_create_service_with_branch_overrides(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $category = ServiceCategory::create([
            'tenant_id' => $tenant->id,
            'name' => 'Hair',
            'slug' => 'hair',
            'is_active' => true,
        ]);
        $colombo = $this->branch($tenant, 'Colombo', 'CMB');
        $galle = $this->branch($tenant, 'Galle', 'GAL');

        $this->actingAs($owner)
            ->post(route('services.store'), [
                'name' => 'Haircut',
                'category_id' => $category->id,
                'description' => 'Classic haircut service.',
                'default_price' => 2500,
                'default_duration_minutes' => 45,
                'is_active' => 1,
                'sort_order' => 1,
                'branches' => [
                    $colombo->id => [
                        'enabled' => 1,
                        'price' => 3000,
                        'duration_minutes' => null,
                        'is_active' => 1,
                    ],
                    $galle->id => [
                        'enabled' => 1,
                        'price' => null,
                        'duration_minutes' => 40,
                        'is_active' => 1,
                    ],
                ],
            ])
            ->assertRedirect();

        $service = Service::where('tenant_id', $tenant->id)->where('slug', 'haircut')->with('branches')->firstOrFail();

        $this->assertSame('2500.00', (string) $service->default_price);
        $this->assertSame(45, $service->default_duration_minutes);
        $this->assertSame('3000.00', $service->effectivePriceFor($colombo));
        $this->assertSame(45, $service->effectiveDurationFor($colombo));
        $this->assertSame('2500.00', $service->effectivePriceFor($galle));
        $this->assertSame(40, $service->effectiveDurationFor($galle));
        $this->assertTrue($service->isAvailableAt($colombo));
    }

    public function test_owner_can_update_service_and_remove_branch_assignment(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $colombo = $this->branch($tenant, 'Colombo', 'CMB');
        $galle = $this->branch($tenant, 'Galle', 'GAL');
        $service = $this->service($tenant, ['name' => 'Facial', 'slug' => 'facial']);
        $service->branches()->sync([
            $colombo->id => ['tenant_id' => $tenant->id, 'price' => null, 'duration_minutes' => null, 'is_active' => true],
            $galle->id => ['tenant_id' => $tenant->id, 'price' => null, 'duration_minutes' => null, 'is_active' => true],
        ]);

        $this->actingAs($owner)
            ->put(route('services.update', $service), [
                'name' => 'Premium Facial',
                'default_price' => 4500,
                'default_duration_minutes' => 60,
                'is_active' => 1,
                'branches' => [
                    $colombo->id => [
                        'enabled' => 1,
                        'price' => 5000,
                        'duration_minutes' => 75,
                        'is_active' => 1,
                    ],
                ],
            ])
            ->assertRedirect(route('services.show', $service));

        $service->refresh()->load('branches');

        $this->assertSame('premium-facial', $service->slug);
        $this->assertSame(1, $service->branches->count());
        $this->assertTrue($service->branches->contains('id', $colombo->id));
        $this->assertFalse($service->branches->contains('id', $galle->id));
        $this->assertSame('5000.00', $service->effectivePriceFor($colombo));
        $this->assertSame(75, $service->effectiveDurationFor($colombo));
    }

    public function test_branch_manager_cannot_create_master_service(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $manager = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);
        $manager->assignRole('Branch Manager');

        $this->actingAs($manager)
            ->post(route('services.store'), [
                'name' => 'Massage',
                'default_price' => 3500,
                'default_duration_minutes' => 60,
            ])
            ->assertForbidden();
    }

    public function test_tenant_cannot_view_other_tenant_service(): void
    {
        [, $owner] = $this->tenantWithOwner();
        [$otherTenant] = $this->tenantWithOwner();
        $otherService = $this->service($otherTenant, ['name' => 'Waxing', 'slug' => 'waxing']);

        $this->actingAs($owner)
            ->get(route('services.show', $otherService))
            ->assertNotFound();
    }

    public function test_category_must_belong_to_current_tenant(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        [$otherTenant] = $this->tenantWithOwner();
        $otherCategory = ServiceCategory::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Hair',
            'slug' => 'other-hair',
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('services.store'), [
                'name' => 'Hair Coloring',
                'category_id' => $otherCategory->id,
                'default_price' => 5000,
                'default_duration_minutes' => 90,
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('category_id');

        $this->assertDatabaseMissing('services', [
            'tenant_id' => $tenant->id,
            'name' => 'Hair Coloring',
        ]);
    }

    public function test_branch_assignment_must_belong_to_current_tenant(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        [$otherTenant] = $this->tenantWithOwner();
        $otherBranch = $this->branch($otherTenant, 'Other Branch', 'OTH');

        $this->actingAs($owner)
            ->post(route('services.store'), [
                'name' => 'Manicure',
                'default_price' => 2500,
                'default_duration_minutes' => 45,
                'is_active' => 1,
                'branches' => [
                    $otherBranch->id => [
                        'enabled' => 1,
                        'is_active' => 1,
                    ],
                ],
            ])
            ->assertSessionHasErrors('branches');

        $this->assertDatabaseMissing('services', [
            'tenant_id' => $tenant->id,
            'name' => 'Manicure',
        ]);
    }

    public function test_same_service_slug_is_allowed_across_tenants_and_rejected_inside_same_tenant(): void
    {
        [$tenantA, $ownerA] = $this->tenantWithOwner();
        [$tenantB, $ownerB] = $this->tenantWithOwner();

        $this->actingAs($ownerA)
            ->post(route('services.store'), [
                'name' => 'Hair Cut',
                'default_price' => 2500,
                'default_duration_minutes' => 45,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->actingAs($ownerB)
            ->post(route('services.store'), [
                'name' => 'Hair Cut',
                'default_price' => 2700,
                'default_duration_minutes' => 45,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->actingAs($ownerA)
            ->post(route('services.store'), [
                'name' => 'Hair Cut',
                'default_price' => 3000,
                'default_duration_minutes' => 45,
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('slug');

        $this->assertSame(2, Service::withoutTenantScope()->where('slug', 'hair-cut')->count());
        $this->assertSame(2, Service::withoutTenantScope()->whereIn('tenant_id', [$tenantA->id, $tenantB->id])->where('name', 'Hair Cut')->count());
    }

    public function test_service_can_be_deactivated(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $service = $this->service($tenant, ['name' => 'Pedicure', 'slug' => 'pedicure']);

        $this->actingAs($owner)
            ->patch(route('services.status.update', $service), ['is_active' => 0])
            ->assertRedirect();

        $this->assertFalse($service->fresh()->is_active);
    }

    private function tenantWithOwner(): array
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
            'features' => ['services' => true],
            'is_active' => true,
        ]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'trialing',
            'starts_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);

        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);
        $owner->assignRole('Salon Owner');

        return [$tenant, $owner];
    }

    private function branch(Tenant $tenant, string $name, string $code): Branch
    {
        return Branch::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'code' => $code,
            'status' => Branch::STATUS_ACTIVE,
            'is_active' => true,
            'is_main' => false,
            'country' => 'LK',
            'currency' => 'LKR',
            'timezone' => 'Asia/Colombo',
        ]);
    }

    private function service(Tenant $tenant, array $overrides = []): Service
    {
        return Service::create(array_merge([
            'tenant_id' => $tenant->id,
            'name' => 'Haircut',
            'slug' => 'haircut',
            'price' => 2500,
            'duration_minutes' => 45,
            'default_price' => 2500,
            'default_duration_minutes' => 45,
            'is_active' => true,
        ], $overrides));
    }
}
