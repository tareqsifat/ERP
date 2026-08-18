<?php

use App\Models\User;
use Modules\FinishedGoods\App\Services\FinishedGoodsStockService;
use Modules\Location\App\Models\Location;
use Modules\Order\App\Models\Order;

// sdd.md §6: covers Modules/Location/routes/api.php's stock-transfer
// routes (StockTransferController), completing the loop the
// LocationModuleTest header comment deferred here.

function seedFgStock(Location $location, Order $order, int $quantity): void
{
    FinishedGoodsStockService::transferIn($location, $order, 'A1', 'BLK', 'M', $quantity, User::factory()->create()->id);
}

test('dispatching a stock transfer deducts source stock and generates a transfer_no', function () {
    actingAsRole('Admin');
    $store = Location::factory()->ofTypeStore()->create();
    $showroom = Location::factory()->ofTypeShowroom()->create();
    $order = Order::factory()->create();
    seedFgStock($store, $order, 20);

    $response = $this->postJson('/api/v1/stock-transfers', [
        'from_location_id' => $store->id,
        'to_location_id' => $showroom->id,
        'order_id' => $order->id,
        'style' => 'A1',
        'color' => 'BLK',
        'size' => 'M',
        'quantity' => 5,
    ]);

    $response->assertCreated();
    $year = now()->year;
    expect($response->json('data.transfer_no'))->toBe("ST-{$year}-0001");
    expect($response->json('data.status'))->toBe('dispatched');

    expect(FinishedGoodsStockService::stockOf($store, $order, 'A1', 'BLK', 'M'))->toBe(15);
});

test('dispatching more than available stock is rejected', function () {
    actingAsRole('Admin');
    $store = Location::factory()->ofTypeStore()->create();
    $showroom = Location::factory()->ofTypeShowroom()->create();
    $order = Order::factory()->create();
    seedFgStock($store, $order, 3);

    $this->postJson('/api/v1/stock-transfers', [
        'from_location_id' => $store->id,
        'to_location_id' => $showroom->id,
        'order_id' => $order->id,
        'style' => 'A1',
        'color' => 'BLK',
        'size' => 'M',
        'quantity' => 5,
    ])->assertStatus(422);
});

test('receiving the full dispatched quantity marks the transfer received and credits the destination', function () {
    actingAsRole('Admin');
    $store = Location::factory()->ofTypeStore()->create();
    $showroom = Location::factory()->ofTypeShowroom()->create();
    $order = Order::factory()->create();
    seedFgStock($store, $order, 20);

    $transfer = $this->postJson('/api/v1/stock-transfers', [
        'from_location_id' => $store->id,
        'to_location_id' => $showroom->id,
        'order_id' => $order->id,
        'style' => 'A1',
        'color' => 'BLK',
        'size' => 'M',
        'quantity' => 10,
    ])->json('data');

    $response = $this->postJson("/api/v1/stock-transfers/{$transfer['id']}/receive", [
        'quantity_received' => 10,
    ]);

    $response->assertOk()->assertJsonPath('data.status', 'received');
    expect(FinishedGoodsStockService::stockOf($showroom, $order, 'A1', 'BLK', 'M'))->toBe(10);
});

test('receiving less than dispatched marks the transfer as a discrepancy', function () {
    actingAsRole('Admin');
    $store = Location::factory()->ofTypeStore()->create();
    $showroom = Location::factory()->ofTypeShowroom()->create();
    $order = Order::factory()->create();
    seedFgStock($store, $order, 20);

    $transfer = $this->postJson('/api/v1/stock-transfers', [
        'from_location_id' => $store->id,
        'to_location_id' => $showroom->id,
        'order_id' => $order->id,
        'style' => 'A1',
        'color' => 'BLK',
        'size' => 'M',
        'quantity' => 10,
    ])->json('data');

    $response = $this->postJson("/api/v1/stock-transfers/{$transfer['id']}/receive", [
        'quantity_received' => 8,
    ]);

    $response->assertOk()->assertJsonPath('data.status', 'discrepancy');
    expect(FinishedGoodsStockService::stockOf($showroom, $order, 'A1', 'BLK', 'M'))->toBe(8);
});

test('a user without stock-transfer.dispatch permission cannot dispatch', function () {
    actingAsRole('Showroom Staff');
    $store = Location::factory()->ofTypeStore()->create();
    $showroom = Location::factory()->ofTypeShowroom()->create();
    $order = Order::factory()->create();
    seedFgStock($store, $order, 20);

    $this->postJson('/api/v1/stock-transfers', [
        'from_location_id' => $store->id,
        'to_location_id' => $showroom->id,
        'order_id' => $order->id,
        'style' => 'A1',
        'color' => 'BLK',
        'size' => 'M',
        'quantity' => 5,
    ])->assertStatus(403);
});

// sdd.md §4: location-scoping — "Showroom Staff sees only their
// showroom" via user.location_id, checked in the controller, not tied
// to the role/permission layer itself.

test('showroom staff can only see and receive stock transfers bound for their own showroom', function () {
    $store = Location::factory()->ofTypeStore()->create();
    $ownShowroom = Location::factory()->ofTypeShowroom()->create();
    $otherShowroom = Location::factory()->ofTypeShowroom()->create();
    $order = Order::factory()->create();
    seedFgStock($store, $order, 40);

    $admin = actingAsRole('Admin');
    $ownTransfer = $this->postJson('/api/v1/stock-transfers', [
        'from_location_id' => $store->id, 'to_location_id' => $ownShowroom->id,
        'order_id' => $order->id, 'style' => 'A1', 'color' => 'BLK', 'size' => 'M', 'quantity' => 5,
    ])->json('data');
    $otherTransfer = $this->postJson('/api/v1/stock-transfers', [
        'from_location_id' => $store->id, 'to_location_id' => $otherShowroom->id,
        'order_id' => $order->id, 'style' => 'A1', 'color' => 'BLK', 'size' => 'M', 'quantity' => 5,
    ])->json('data');

    actingAsRole('Showroom Staff', ['location_id' => $ownShowroom->id]);

    $this->getJson('/api/v1/stock-transfers')->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ownTransfer['id']);

    $this->getJson("/api/v1/stock-transfers/{$otherTransfer['id']}")->assertStatus(403);

    $this->postJson("/api/v1/stock-transfers/{$otherTransfer['id']}/receive", ['quantity_received' => 5])
        ->assertStatus(403);

    $this->postJson("/api/v1/stock-transfers/{$ownTransfer['id']}/receive", ['quantity_received' => 5])
        ->assertOk()->assertJsonPath('data.status', 'received');
});
