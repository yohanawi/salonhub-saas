<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Inventory;
use App\Models\Service;
use App\Models\StockMovement;
use App\Services\Inventory\InventoryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\BuildsSalonFixtures;
use Tests\TestCase;

class ServiceExtrasTest extends TestCase
{
    use BuildsSalonFixtures;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_service_saves_image_online_listing_addons_and_consumables(): void
    {
        Storage::fake('public');
        [$tenant, $owner, $branch] = $this->makeSalon();
        $wash = $this->makeService($tenant, $branch, ['name' => 'Hair Wash', 'is_addon' => true]);
        $shampoo = $this->makeProduct($tenant, ['name' => 'Shampoo']);

        $this->actingAs($owner)->post(route('services.store'), [
            'name' => 'Hair Cut Deluxe',
            'default_price' => 3000,
            'default_duration_minutes' => 45,
            'is_active' => 1,
            'branches' => [$branch->id => ['enabled' => 1, 'is_active' => 1]],
            'image' => UploadedFile::fake()->image('cut.jpg', 400, 300),
            'online_description' => 'Wash, cut and blow-dry.',
            'show_online' => 1,
            'is_addon' => 0,
            'addons_present' => 1,
            'addon_ids' => [$wash->id],
            'consumables_present' => 1,
            'consumables' => [['product_id' => $shampoo->id, 'quantity' => 2]],
        ])->assertRedirect();

        $service = Service::withoutTenantScope()->where('tenant_id', $tenant->id)->where('name', 'Hair Cut Deluxe')->firstOrFail();

        $this->assertNotNull($service->image_path);
        Storage::disk('public')->assertExists($service->image_path);
        $this->assertSame('Wash, cut and blow-dry.', $service->online_description);
        $this->assertTrue($service->show_online);
        $this->assertSame([$wash->id], $service->addons->pluck('id')->all());
        $this->assertSame(2, (int) $service->consumables->first()->pivot->quantity);

        $this->actingAs($owner)->get(route('services.show', $service))
            ->assertOk()
            ->assertSee('Suggested Add-ons')
            ->assertSee('Hair Wash')
            ->assertSee('Shampoo');

        $this->actingAs($owner)->get(route('services.edit', $service))->assertOk()->assertSee('Products Used (Consumables)');

        // Removing the image and clearing add-ons/consumables on edit.
        $this->actingAs($owner)->put(route('services.update', $service), [
            'name' => 'Hair Cut Deluxe',
            'default_price' => 3000,
            'default_duration_minutes' => 45,
            'is_active' => 1,
            'branches' => [$branch->id => ['enabled' => 1, 'is_active' => 1]],
            'remove_image' => 1,
            'addons_present' => 1,
            'consumables_present' => 1,
        ])->assertRedirect();

        $service->refresh();
        $this->assertNull($service->image_path);
        $this->assertCount(0, $service->addons);
        $this->assertCount(0, $service->consumables);
    }

    public function test_service_rejects_products_and_addons_from_another_salon(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        [$otherTenant, , $otherBranch] = $this->makeSalon();
        $foreignProduct = $this->makeProduct($otherTenant);
        $foreignService = $this->makeService($otherTenant, $otherBranch);

        $this->actingAs($owner)->post(route('services.store'), [
            'name' => 'Colour',
            'default_price' => 5000,
            'default_duration_minutes' => 90,
            'branches' => [$branch->id => ['enabled' => 1, 'is_active' => 1]],
            'addon_ids' => [$foreignService->id],
            'consumables' => [['product_id' => $foreignProduct->id, 'quantity' => 1]],
        ])->assertSessionHasErrors(['addon_ids', 'consumables']);
    }

    public function test_booking_form_offers_service_addons(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        $cut = $this->makeService($tenant, $branch, ['name' => 'Hair Cut']);
        $wash = $this->makeService($tenant, $branch, ['name' => 'Hair Wash']);
        $cut->addons()->attach($wash->id, ['tenant_id' => $tenant->id, 'sort_order' => 0]);

        $this->actingAs($owner)->get(route('appointment-management.appointments.create'))
            ->assertOk()
            ->assertSee('data-addon-suggestions', false)
            ->assertSee('"' . $cut->id . '":[{"id":' . $wash->id . ',"name":"Hair Wash"', false);
    }

    public function test_completing_appointment_deducts_consumables_once_and_reports_shortages(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        $service = $this->makeService($tenant, $branch, ['name' => 'Facial']);
        $cream = $this->makeProduct($tenant, ['name' => 'Face Cream']);
        $mask = $this->makeProduct($tenant, ['name' => 'Sheet Mask']);
        $service->consumables()->attach([
            $cream->id => ['tenant_id' => $tenant->id, 'quantity' => 2],
            $mask->id => ['tenant_id' => $tenant->id, 'quantity' => 1],
        ]);

        app(InventoryService::class)->openingStock($cream, $branch, 10, 100);
        // No mask stock at all: completion must still succeed and report it.

        $appointment = $this->inProgressAppointment($tenant, $branch, $owner, $service);

        $this->actingAs($owner)
            ->from(route('appointment-management.appointments.show', $appointment))
            ->post(route('appointment-management.appointments.complete', $appointment))
            ->assertRedirect()
            ->assertSessionHas('warning', fn ($message) => str_contains($message, 'Sheet Mask'));

        $this->assertSame(Appointment::STATUS_COMPLETED, $appointment->fresh()->status);
        $this->assertSame(8, $this->onHand($cream, $branch));

        $movement = StockMovement::withoutTenantScope()->where('product_id', $cream->id)->where('type', StockMovement::TYPE_SERVICE_CONSUMPTION)->firstOrFail();
        $this->assertSame(-2, $movement->quantity);
        $this->assertSame($appointment->id, (int) $movement->reference_id);

        // A replayed completion never deducts twice.
        app(\App\Services\Inventory\ServiceConsumptionService::class)->consumeForAppointment($appointment->fresh(), $owner);
        $this->assertSame(8, $this->onHand($cream, $branch));

        // The shortfall is kept on the appointment, not just flashed once.
        $this->assertSame(
            [['product_id' => $mask->id, 'name' => 'Sheet Mask', 'quantity' => 1]],
            $appointment->fresh()->stock_shortages
        );

        $this->actingAs($owner)
            ->get(route('appointment-management.appointments.show', $appointment))
            ->assertSee('Service products still to be deducted');

        // After restocking, a retry deducts only what was still owed.
        app(InventoryService::class)->openingStock($mask, $branch, 5, 50);

        $this->actingAs($owner)
            ->post(route('appointment-management.appointments.consume-stock', $appointment))
            ->assertSessionHas('status', 'Service products deducted from stock.');

        $this->assertSame(4, $this->onHand($mask, $branch));
        $this->assertSame(8, $this->onHand($cream, $branch));
        $this->assertNull($appointment->fresh()->stock_shortages);
    }

    private function inProgressAppointment($tenant, $branch, $owner, Service $service): Appointment
    {
        $staff = $this->makeStaff($tenant, $branch);
        $customer = $this->makeCustomer($tenant, $branch);

        $appointment = Appointment::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'appointment_number' => 'APT-' . fake()->unique()->numberBetween(100000, 999999),
            'status' => Appointment::STATUS_IN_PROGRESS,
            'starts_at' => now()->subHour(),
            'ends_at' => now(),
            'created_by' => $owner->id,
        ]);

        AppointmentService::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $appointment->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'service_name' => $service->name,
            'duration_minutes' => 60,
            'status' => Appointment::STATUS_IN_PROGRESS,
        ]);

        return $appointment;
    }

    private function onHand($product, $branch): int
    {
        return (int) Inventory::withoutTenantScope()->where('product_id', $product->id)->where('branch_id', $branch->id)->value('quantity_on_hand');
    }
}
