<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\ProductBatch;
use App\Models\Purchase;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\StockTake;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\InventoryAlertNotification;
use App\Services\Inventory\InventoryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Concerns\BuildsSalonFixtures;
use Tests\TestCase;

class InventoryProcurementTest extends TestCase
{
    use BuildsSalonFixtures;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_owner_manages_suppliers(): void
    {
        [$tenant, $owner] = $this->makeSalon();

        $this->actingAs($owner)->post(route('inventory.suppliers.store'), [
            'name' => 'Beauty Wholesale',
            'contact_person' => 'Kamal',
            'phone' => '0112345678',
            'email' => 'sales@beauty.test',
            'is_active' => 1,
        ])->assertRedirect(route('inventory.suppliers.index'));

        $supplier = Supplier::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertSame('Kamal', $supplier->contact_person);

        // Duplicate names inside one salon are rejected.
        $this->actingAs($owner)->post(route('inventory.suppliers.store'), ['name' => 'Beauty Wholesale'])
            ->assertSessionHasErrors('name');

        $this->actingAs($owner)->put(route('inventory.suppliers.update', $supplier), [
            'name' => 'Beauty Wholesale Ltd',
            'is_active' => 0,
        ])->assertRedirect();

        $this->assertFalse($supplier->fresh()->is_active);
        $this->actingAs($owner)->get(route('inventory.suppliers.index'))->assertOk()->assertSee('Beauty Wholesale Ltd');
        $this->actingAs($owner)->get(route('inventory.suppliers.create'))->assertOk();
        $this->actingAs($owner)->get(route('inventory.suppliers.edit', $supplier))->assertOk();
        $this->actingAs($owner)->get(route('inventory.purchases.create'))->assertOk()->assertSee('Order Details');
        $this->actingAs($owner)->get(route('inventory.batches.index', ['filter' => 'all']))->assertOk()->assertSee('Batches in stock');
        $this->actingAs($owner)->get(route('inventory.products.create'))->assertOk()->assertSee('Track Batches');
        $this->actingAs($owner)->get(route('inventory.dashboard'))->assertOk()->assertSee('Purchase Orders');
    }

    public function test_purchase_order_lifecycle_with_partial_and_full_receipt(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        $supplier = Supplier::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'name' => 'Lanka Supplies', 'is_active' => true]);
        $shampoo = $this->makeProduct($tenant, ['name' => 'Shampoo']);
        $serum = $this->makeProduct($tenant, ['name' => 'Serum', 'track_batches' => true]);

        $this->actingAs($owner)->post(route('inventory.purchases.store'), [
            'branch_id' => $branch->id,
            'supplier_id' => $supplier->id,
            'purchase_date' => today()->toDateString(),
            'discount' => 100,
            'tax' => 50,
            'items' => [
                ['product_id' => $shampoo->id, 'quantity' => 10, 'unit_cost' => 200],
                ['product_id' => $serum->id, 'quantity' => 5, 'unit_cost' => 1000],
                ['product_id' => '', 'quantity' => 1, 'unit_cost' => 0], // blank row from the form
            ],
        ])->assertRedirect();

        $purchase = Purchase::withoutTenantScope()->where('tenant_id', $tenant->id)->with('items')->firstOrFail();
        $this->assertSame(Purchase::STATUS_DRAFT, $purchase->status);
        $this->assertStringStartsWith('PO-', $purchase->purchase_number);
        $this->assertEquals('7000.00', $purchase->subtotal);
        $this->assertEquals('6950.00', $purchase->total);
        $this->assertCount(2, $purchase->items);

        // Drafts cannot be received yet.
        $this->actingAs($owner)->post(route('inventory.purchases.receive', $purchase), ['items' => []])->assertForbidden();

        $this->actingAs($owner)->post(route('inventory.purchases.order', $purchase))->assertRedirect();
        $this->assertSame(Purchase::STATUS_ORDERED, $purchase->fresh()->status);

        $shampooLine = $purchase->items->firstWhere('product_id', $shampoo->id);
        $serumLine = $purchase->items->firstWhere('product_id', $serum->id);

        // Over-receiving is refused.
        $this->actingAs($owner)->post(route('inventory.purchases.receive', $purchase), [
            'items' => [$shampooLine->id => ['quantity' => 11]],
        ])->assertSessionHasErrors();

        // First delivery: part of the shampoo and all of the serum with batch + expiry.
        $this->actingAs($owner)->post(route('inventory.purchases.receive', $purchase), [
            'supplier_reference' => 'DN-001',
            'items' => [
                $shampooLine->id => ['quantity' => 4],
                $serumLine->id => ['quantity' => 5, 'batch_number' => 'SR-24', 'expiry_date' => today()->addMonths(6)->toDateString()],
            ],
        ])->assertRedirect();

        $this->assertSame(Purchase::STATUS_PARTIALLY_RECEIVED, $purchase->fresh()->status);
        $this->assertSame(4, $this->onHand($shampoo, $branch));
        $this->assertSame(5, $this->onHand($serum, $branch));

        $batch = ProductBatch::withoutTenantScope()->where('product_id', $serum->id)->firstOrFail();
        $this->assertSame('SR-24', $batch->batch_number);
        $this->assertSame(5, $batch->quantity_remaining);

        // Cancelling is no longer possible once goods arrived.
        $this->actingAs($owner)->post(route('inventory.purchases.cancel', $purchase))->assertForbidden();

        // Second delivery completes the order.
        $this->actingAs($owner)->post(route('inventory.purchases.receive', $purchase), [
            'items' => [$shampooLine->id => ['quantity' => 6]],
        ])->assertRedirect();

        $purchase->refresh();
        $this->assertSame(Purchase::STATUS_RECEIVED, $purchase->status);
        $this->assertNotNull($purchase->received_at);
        $this->assertSame(10, $this->onHand($shampoo, $branch));
        $this->assertSame(2, $purchase->receipts()->count());
        $this->assertSame(3, StockMovement::withoutTenantScope()->where('tenant_id', $tenant->id)->where('type', StockMovement::TYPE_PURCHASE_RECEIPT)->count());

        $this->actingAs($owner)->get(route('inventory.purchases.show', $purchase))->assertOk()->assertSee('Goods Received Notes')->assertSee('SR-24');
        $this->actingAs($owner)->get(route('inventory.purchases.print', $purchase))->assertOk()->assertSee($purchase->purchase_number);
        $this->actingAs($owner)->get(route('inventory.purchases.index'))->assertOk()->assertSee($purchase->purchase_number);
    }

    public function test_draft_purchase_can_be_edited_and_cancelled(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        $product = $this->makeProduct($tenant);

        $this->actingAs($owner)->post(route('inventory.purchases.store'), [
            'branch_id' => $branch->id,
            'purchase_date' => today()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_cost' => 50]],
        ]);
        $purchase = Purchase::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->actingAs($owner)->get(route('inventory.purchases.edit', $purchase))->assertOk();
        $this->actingAs($owner)->put(route('inventory.purchases.update', $purchase), [
            'branch_id' => $branch->id,
            'purchase_date' => today()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 8, 'unit_cost' => 50]],
        ])->assertRedirect();

        $this->assertEquals('400.00', $purchase->fresh()->total);

        $this->actingAs($owner)->post(route('inventory.purchases.cancel', $purchase))->assertRedirect();
        $this->assertSame(Purchase::STATUS_CANCELLED, $purchase->fresh()->status);
        $this->actingAs($owner)->get(route('inventory.purchases.edit', $purchase))->assertForbidden();
    }

    public function test_reorder_low_stock_prefills_purchase_order(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        $low = $this->makeProduct($tenant, ['name' => 'Low Gel', 'reorder_level' => 5]);
        $fine = $this->makeProduct($tenant, ['name' => 'Plenty Wax', 'reorder_level' => 5]);
        app(InventoryService::class)->openingStock($low, $branch, 2, 100);
        app(InventoryService::class)->openingStock($fine, $branch, 50, 100);

        $response = $this->actingAs($owner)->get(route('inventory.purchases.create', ['reorder' => 1, 'branch_id' => $branch->id]))->assertOk();

        $rows = collect($response->viewData('itemRows'));
        $this->assertSame([$low->id], $rows->pluck('product_id')->all());
        $this->assertSame(8, $rows->first()['quantity']);
    }

    public function test_staff_without_purchase_permissions_are_blocked_and_salons_are_isolated(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        [$otherTenant, $otherOwner] = $this->makeSalon();
        $beautician = User::factory()->create(['tenant_id' => $tenant->id, 'email_verified_at' => now()]);
        $beautician->assignRole('Beautician');

        $product = $this->makeProduct($tenant);
        $this->actingAs($owner)->post(route('inventory.purchases.store'), [
            'branch_id' => $branch->id,
            'purchase_date' => today()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => 10]],
        ]);
        $purchase = Purchase::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->actingAs($beautician)->get(route('inventory.purchases.index'))->assertForbidden();
        $this->actingAs($beautician)->get(route('inventory.suppliers.index'))->assertForbidden();
        $this->actingAs($beautician)->get(route('inventory.stock-takes.index'))->assertForbidden();
        // Another salon's records are invisible to route binding (tenant scope), so they 404.
        $this->actingAs($otherOwner)->get(route('inventory.purchases.show', $purchase))->assertNotFound();

        // Products from another salon cannot be ordered.
        $foreign = $this->makeProduct($otherTenant);
        $this->actingAs($owner)->post(route('inventory.purchases.store'), [
            'branch_id' => $branch->id,
            'purchase_date' => today()->toDateString(),
            'items' => [['product_id' => $foreign->id, 'quantity' => 1, 'unit_cost' => 10]],
        ])->assertSessionHasErrors('items');
    }

    public function test_batches_are_consumed_first_expiry_first_and_can_be_written_off(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        $serum = $this->makeProduct($tenant, ['name' => 'Serum', 'track_batches' => true]);
        $inventory = app(InventoryService::class);

        $inventory->increase($serum, $branch, 5, StockMovement::TYPE_PURCHASE_RECEIPT, 100, $owner, null, null, null, ['batch_number' => 'LATE', 'expiry_date' => today()->addMonths(6)->toDateString()]);
        $inventory->increase($serum, $branch, 3, StockMovement::TYPE_PURCHASE_RECEIPT, 100, $owner, null, null, null, ['batch_number' => 'SOON', 'expiry_date' => today()->addDays(10)->toDateString()]);
        $inventory->increase($serum, $branch, 2, StockMovement::TYPE_PURCHASE_RECEIPT, 100, $owner, null, null, null, ['batch_number' => 'OLD', 'expiry_date' => today()->subDay()->toDateString()]);

        // Selling 4 uses the expired batch first, then the soonest-expiring one.
        $inventory->decrease($serum, $branch, 4, StockMovement::TYPE_POS_SALE, $owner);

        $remaining = ProductBatch::withoutTenantScope()->where('product_id', $serum->id)->pluck('quantity_remaining', 'batch_number');
        $this->assertSame(0, $remaining['OLD']);
        $this->assertSame(1, $remaining['SOON']);
        $this->assertSame(5, $remaining['LATE']);

        $this->actingAs($owner)->get(route('inventory.batches.index', ['filter' => 'expiring']))
            ->assertOk()->assertSee('SOON')->assertDontSee('LATE');

        $soon = ProductBatch::withoutTenantScope()->where('batch_number', 'SOON')->firstOrFail();
        $this->actingAs($owner)->post(route('inventory.batches.write-off', $soon), ['quantity' => 1, 'reason' => 'damaged'])->assertRedirect();

        $this->assertSame(0, $soon->fresh()->quantity_remaining);
        $this->assertSame(5, $this->onHand($serum, $branch));
        $this->assertTrue(StockMovement::withoutTenantScope()->where('type', StockMovement::TYPE_DAMAGED)->where('product_id', $serum->id)->exists());

        // Nothing left in the batch: further write-offs are refused.
        $this->actingAs($owner)->post(route('inventory.batches.write-off', $soon), ['quantity' => 1, 'reason' => 'damaged'])->assertForbidden();
    }

    public function test_stock_take_posts_counted_differences_on_top_of_current_stock(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        $gel = $this->makeProduct($tenant, ['name' => 'Gel']);
        $wax = $this->makeProduct($tenant, ['name' => 'Wax']);
        $spray = $this->makeProduct($tenant, ['name' => 'Spray']);
        $inventory = app(InventoryService::class);
        $inventory->openingStock($gel, $branch, 10, 50);
        $inventory->openingStock($wax, $branch, 4, 50);
        $inventory->openingStock($spray, $branch, 7, 50);

        $this->actingAs($owner)->post(route('inventory.stock-takes.store'), ['branch_id' => $branch->id])->assertRedirect();
        $stockTake = StockTake::withoutTenantScope()->where('tenant_id', $tenant->id)->with('items')->firstOrFail();
        $this->assertCount(3, $stockTake->items);

        $items = $stockTake->items->keyBy('product_id');

        // Save progress first, then a sale happens while counting.
        $this->actingAs($owner)->put(route('inventory.stock-takes.update', $stockTake), [
            'counts' => [$items[$gel->id]->id => 8],
            'action' => 'save',
        ])->assertRedirect();
        $this->assertSame(-2, $items[$gel->id]->fresh()->variance);

        $inventory->decrease($gel, $branch, 1, StockMovement::TYPE_POS_SALE, $owner);

        $this->actingAs($owner)->put(route('inventory.stock-takes.update', $stockTake), [
            'counts' => [
                $items[$gel->id]->id => 8,   // 2 missing
                $items[$wax->id]->id => 6,   // 2 extra
                $items[$spray->id]->id => '', // not counted
            ],
            'action' => 'complete',
        ])->assertRedirect();

        $stockTake->refresh();
        $this->assertSame(StockTake::STATUS_COMPLETED, $stockTake->status);
        $this->assertNotNull($stockTake->stock_adjustment_id);
        $this->assertSame(StockAdjustment::REASON_PHYSICAL_COUNT, $stockTake->adjustment->reason);

        $this->assertSame(7, $this->onHand($gel, $branch));   // 10 - 1 sold - 2 missing
        $this->assertSame(6, $this->onHand($wax, $branch));
        $this->assertSame(7, $this->onHand($spray, $branch)); // untouched

        $this->actingAs($owner)->get(route('inventory.stock-takes.show', $stockTake))->assertOk()->assertSee($stockTake->adjustment->adjustment_number);
        $this->actingAs($owner)->get(route('inventory.stock-takes.print', $stockTake))->assertOk()->assertSee('Stock Count Sheet');

        // Closed counts cannot be changed.
        $this->actingAs($owner)->put(route('inventory.stock-takes.update', $stockTake), ['counts' => []])->assertForbidden();
    }

    public function test_cancelled_stock_take_changes_nothing(): void
    {
        [$tenant, $owner, $branch] = $this->makeSalon();
        $gel = $this->makeProduct($tenant);
        app(InventoryService::class)->openingStock($gel, $branch, 10, 50);

        $this->actingAs($owner)->post(route('inventory.stock-takes.store'), ['branch_id' => $branch->id]);
        $stockTake = StockTake::withoutTenantScope()->where('tenant_id', $tenant->id)->with('items')->firstOrFail();

        $this->actingAs($owner)->put(route('inventory.stock-takes.update', $stockTake), ['counts' => [$stockTake->items->first()->id => 1]]);
        $this->actingAs($owner)->post(route('inventory.stock-takes.cancel', $stockTake))->assertRedirect();

        $this->assertSame(StockTake::STATUS_CANCELLED, $stockTake->fresh()->status);
        $this->assertSame(10, $this->onHand($gel, $branch));
        $this->actingAs($owner)->get(route('inventory.stock-takes.index'))->assertOk()->assertSee($stockTake->stock_take_number);
    }

    public function test_low_stock_and_expiry_alerts_are_sent_once_per_incident(): void
    {
        Notification::fake();
        [$tenant, $owner, $branch] = $this->makeSalon();
        $beautician = User::factory()->create(['tenant_id' => $tenant->id, 'email_verified_at' => now()]);
        $beautician->assignRole('Beautician');

        $gel = $this->makeProduct($tenant, ['name' => 'Gel', 'reorder_level' => 5]);
        $serum = $this->makeProduct($tenant, ['name' => 'Serum', 'track_batches' => true]);
        $inventory = app(InventoryService::class);
        $inventory->openingStock($gel, $branch, 3, 50);
        $inventory->increase($serum, $branch, 4, StockMovement::TYPE_PURCHASE_RECEIPT, 100, $owner, null, null, null, ['batch_number' => 'EXP1', 'expiry_date' => today()->addDays(5)->toDateString()]);

        $this->artisan('inventory:send-alerts', ['--tenant_id' => $tenant->id])->assertSuccessful();

        Notification::assertSentTo($owner, InventoryAlertNotification::class, function (InventoryAlertNotification $notification) {
            return $notification->lowStock->pluck('product')->all() === ['Gel']
                && $notification->expiring->pluck('batch')->all() === ['EXP1'];
        });
        Notification::assertNotSentTo($beautician, InventoryAlertNotification::class);

        // Running again does not repeat the same alert.
        Notification::fake();
        $this->artisan('inventory:send-alerts', ['--tenant_id' => $tenant->id])->assertSuccessful();
        Notification::assertNothingSent();

        // Restock above the reorder level, then dip again: a fresh alert goes out.
        $inventory->increase($gel, $branch, 10, StockMovement::TYPE_PURCHASE_RECEIPT, 50, $owner);
        $this->assertNull(Inventory::withoutTenantScope()->where('product_id', $gel->id)->value('low_stock_notified_at'));
        $inventory->decrease($gel, $branch, 10, StockMovement::TYPE_POS_SALE, $owner);

        $this->artisan('inventory:send-alerts', ['--tenant_id' => $tenant->id])->assertSuccessful();
        Notification::assertSentTo($owner, InventoryAlertNotification::class, fn ($notification) => $notification->lowStock->count() === 1 && $notification->expiring->isEmpty());
    }

    private function onHand($product, $branch): int
    {
        return (int) Inventory::withoutTenantScope()->where('product_id', $product->id)->where('branch_id', $branch->id)->value('quantity_on_hand');
    }
}
