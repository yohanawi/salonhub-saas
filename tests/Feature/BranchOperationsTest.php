<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\Inventory;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\User;
use App\Services\Billing\TaxService;
use App\Services\Inventory\InventoryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Concerns\BuildsSalonFixtures;
use Tests\TestCase;

class BranchOperationsTest extends TestCase
{
    use BuildsSalonFixtures;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_stock_moves_between_branches_through_transit(): void
    {
        [$tenant, $owner, $colombo] = $this->makeSalon();
        $kandy = $this->branch($tenant, 'Kandy');
        $gel = $this->makeProduct($tenant, ['name' => 'Gel']);
        $wax = $this->makeProduct($tenant, ['name' => 'Wax']);
        $inventory = app(InventoryService::class);
        $inventory->openingStock($gel, $colombo, 10, 120);
        $inventory->openingStock($wax, $colombo, 5, 80);

        $this->actingAs($owner)->get(route('inventory.transfers.create'))->assertOk()->assertSee('New Stock Transfer');

        $this->actingAs($owner)->post(route('inventory.transfers.store'), [
            'from_branch_id' => $colombo->id,
            'to_branch_id' => $kandy->id,
            'items' => [['product_id' => $gel->id, 'quantity' => 4], ['product_id' => $wax->id, 'quantity' => 2], ['product_id' => '', 'quantity' => 1]],
            'send_now' => 1,
        ])->assertRedirect();

        $transfer = StockTransfer::withoutTenantScope()->where('tenant_id', $tenant->id)->with('items')->firstOrFail();
        $this->assertSame(StockTransfer::STATUS_IN_TRANSIT, $transfer->status);
        $this->assertSame(6, $this->onHand($gel, $colombo));
        $this->assertSame(0, $this->onHand($gel, $kandy));
        $this->assertEquals('120.00', $transfer->items->firstWhere('product_id', $gel->id)->unit_cost);

        // One wax was broken on the way.
        $waxLine = $transfer->items->firstWhere('product_id', $wax->id);
        $this->actingAs($owner)->post(route('inventory.transfers.receive', $transfer), ['received' => [$waxLine->id => 1]])->assertRedirect();

        $transfer->refresh();
        $this->assertSame(StockTransfer::STATUS_RECEIVED, $transfer->status);
        $this->assertSame(4, $this->onHand($gel, $kandy));
        $this->assertSame(1, $this->onHand($wax, $kandy));
        $this->assertStringContainsString('Wax: 1 short', $transfer->notes);
        $this->assertSame(2, StockMovement::withoutTenantScope()->where('type', StockMovement::TYPE_TRANSFER_IN)->where('branch_id', $kandy->id)->count());

        $this->actingAs($owner)->get(route('inventory.transfers.show', $transfer))->assertOk()->assertSee($transfer->transfer_number);
        $this->actingAs($owner)->get(route('inventory.transfers.index'))->assertOk()->assertSee('Received');
    }

    public function test_cancelling_in_transit_returns_stock_and_overdrawing_is_refused(): void
    {
        [$tenant, $owner, $colombo] = $this->makeSalon();
        $kandy = $this->branch($tenant, 'Kandy');
        $gel = $this->makeProduct($tenant);
        app(InventoryService::class)->openingStock($gel, $colombo, 3, 100);

        $this->actingAs($owner)->post(route('inventory.transfers.store'), [
            'from_branch_id' => $colombo->id, 'to_branch_id' => $kandy->id, 'items' => [['product_id' => $gel->id, 'quantity' => 5]], 'send_now' => 1,
        ])->assertSessionHasErrors('stock');
        $this->assertSame(3, $this->onHand($gel, $colombo));

        $this->actingAs($owner)->post(route('inventory.transfers.store'), [
            'from_branch_id' => $colombo->id, 'to_branch_id' => $kandy->id, 'items' => [['product_id' => $gel->id, 'quantity' => 2]], 'send_now' => 1,
        ])->assertRedirect();
        $transfer = StockTransfer::withoutTenantScope()->where('tenant_id', $tenant->id)->where('status', StockTransfer::STATUS_IN_TRANSIT)->firstOrFail();
        $this->assertSame(1, $this->onHand($gel, $colombo));

        $this->actingAs($owner)->post(route('inventory.transfers.cancel', $transfer))->assertRedirect();
        $this->assertSame(StockTransfer::STATUS_CANCELLED, $transfer->fresh()->status);
        $this->assertSame(3, $this->onHand($gel, $colombo));

        // Same branch on both ends is refused.
        $this->actingAs($owner)->post(route('inventory.transfers.store'), [
            'from_branch_id' => $colombo->id, 'to_branch_id' => $colombo->id, 'items' => [['product_id' => $gel->id, 'quantity' => 1]],
        ])->assertSessionHasErrors('to_branch_id');

        $beautician = User::factory()->create(['tenant_id' => $tenant->id, 'email_verified_at' => now()]);
        $beautician->assignRole('Beautician');
        $this->actingAs($beautician)->get(route('inventory.transfers.index'))->assertForbidden();
    }

    public function test_branch_price_list_and_tax(): void
    {
        [$tenant, $owner, $colombo] = $this->makeSalon();
        $kandy = $this->branch($tenant, 'Kandy');
        $cut = $this->makeService($tenant, $colombo, ['name' => 'Hair Cut', 'default_price' => 3000]);
        $gel = $this->makeProduct($tenant, ['name' => 'Gel', 'selling_price' => 1000]);

        app(\App\Services\Settings\SettingService::class)->setMany($tenant, null, ['tax.enabled' => true, 'tax.rate' => 18, 'tax.name' => 'VAT'], $owner);

        $this->actingAs($owner)->get(route('branches.pricing.edit', $kandy))->assertOk()->assertSee('Hair Cut')->assertSee('Gel');

        $this->actingAs($owner)->put(route('branches.pricing.update', $kandy), [
            'services' => [$cut->id => ['enabled' => 1, 'price' => 2500, 'duration_minutes' => 50]],
            'products' => [$gel->id => 900],
            'tax_mode' => 'branch',
            'tax_enabled' => 0,
            'tax_name' => 'VAT',
            'tax_rate' => 0,
        ])->assertRedirect(route('branches.pricing.edit', $kandy));

        $cut->refresh()->load('branches');
        $this->assertTrue($cut->isAvailableAt($kandy));
        $this->assertSame('2500.00', $cut->effectivePriceFor($kandy));
        $this->assertSame(50, $cut->effectiveDurationFor($kandy));
        $this->assertSame('3000.00', $cut->effectivePriceFor($colombo));

        $this->assertEquals(900.0, $gel->priceAt($kandy));
        $this->assertEquals(1000.0, $gel->priceAt($colombo));

        $taxes = app(TaxService::class);
        $this->assertFalse($taxes->configFor($kandy)['enabled']);
        $this->assertTrue($taxes->configFor($colombo)['enabled']);

        // Back to salon-wide tax, and clearing the product price removes the override.
        $this->actingAs($owner)->put(route('branches.pricing.update', $kandy), [
            'services' => [$cut->id => ['enabled' => 0]],
            'products' => [$gel->id => ''],
            'tax_mode' => 'salon',
        ])->assertRedirect();

        $this->assertTrue($taxes->configFor($kandy->fresh())['enabled']);
        $this->assertFalse(BranchProduct::withoutTenantScope()->where('branch_id', $kandy->id)->exists());
        $this->assertFalse($cut->fresh()->load('branches')->isAvailableAt($kandy));
    }

    public function test_pos_screens_show_branch_prices(): void
    {
        [$tenant, $owner, $colombo] = $this->makeSalon();
        $gel = $this->makeProduct($tenant, ['name' => 'Gel', 'selling_price' => 1000]);
        BranchProduct::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'branch_id' => $colombo->id, 'product_id' => $gel->id, 'selling_price' => 850]);

        $products = \App\Models\Product::applyBranchPrices(collect([$gel->fresh()]), $colombo->id);
        $this->assertEquals('850.00', $products->first()->selling_price);
        $this->assertSame(1000.0, (float) $gel->fresh()->selling_price);
    }

    private function branch($tenant, string $name): Branch
    {
        return Branch::withoutTenantScope()->create([
            'tenant_id' => $tenant->id, 'name' => $name, 'code' => strtoupper(substr($name, 0, 3)) . '-' . fake()->unique()->numberBetween(100, 999),
            'status' => Branch::STATUS_ACTIVE, 'is_active' => true, 'country' => 'LK', 'currency' => 'LKR', 'timezone' => 'Asia/Colombo',
        ]);
    }

    private function onHand($product, $branch): int
    {
        return (int) Inventory::withoutTenantScope()->where('product_id', $product->id)->where('branch_id', $branch->id)->value('quantity_on_hand');
    }
}
