<?php

use App\Models\User;
use Modules\FinishedGoods\App\Services\FinishedGoodsStockService;
use Modules\Location\App\Models\Location;
use Modules\Order\App\Models\Order;

// sdd.md §6: FinishedGoods is read-only (stock only moves as a side
// effect of QC pass / Stock Transfer / Shipment — see those modules'
// tests). This covers the two read endpoints in
// Modules/FinishedGoods/routes/api.php against a ledger seeded directly
// through the service.

test('stock endpoint returns a grouped, non-zero ledger aggregate', function () {
    $user = actingAsRole('Admin');
    $store = Location::factory()->ofTypeStore()->create();
    $order = Order::factory()->create();

    FinishedGoodsStockService::transferIn($store, $order, 'A1', 'BLK', 'M', 10, $user->id);
    FinishedGoodsStockService::transferOut($store, $order, 'A1', 'BLK', 'M', 4, $user->id);

    $response = $this->getJson('/api/v1/finished-goods/stock?'.http_build_query(['location_id' => $store->id]));

    $response->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.quantity', 6);
});

test('a location with zero net stock is excluded from the stock endpoint', function () {
    $user = actingAsRole('Admin');
    $store = Location::factory()->ofTypeStore()->create();
    $order = Order::factory()->create();

    FinishedGoodsStockService::transferIn($store, $order, 'A1', 'BLK', 'M', 5, $user->id);
    FinishedGoodsStockService::transferOut($store, $order, 'A1', 'BLK', 'M', 5, $user->id);

    $this->getJson('/api/v1/finished-goods/stock?'.http_build_query(['location_id' => $store->id]))
        ->assertOk()->assertJsonCount(0, 'data');
});

test('movements endpoint lists the ledger filterable by type', function () {
    $user = actingAsRole('Admin');
    $store = Location::factory()->ofTypeStore()->create();
    $order = Order::factory()->create();

    FinishedGoodsStockService::transferIn($store, $order, 'A1', 'BLK', 'M', 10, $user->id);
    FinishedGoodsStockService::transferOut($store, $order, 'A1', 'BLK', 'M', 3, $user->id);

    $this->getJson('/api/v1/finished-goods/movements?type=transfer_out')
        ->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.quantity', -3);
});

test('a user without finished-goods.view permission cannot read stock', function () {
    actingAsRole('Line Supervisor');

    $this->getJson('/api/v1/finished-goods/stock')->assertStatus(403);
});

// sdd.md §4: location-scoping is a plain `location_id` on the user
// record, checked in the query — a Showroom Staff user is confined to
// their own location's stock regardless of what the request asks for.
test('a showroom staff user only sees stock for their own location, even if another is requested', function () {
    $showroomA = Location::factory()->ofTypeShowroom()->create();
    $showroomB = Location::factory()->ofTypeShowroom()->create();
    $order = Order::factory()->create();

    $admin = actingAsRole('Admin');
    FinishedGoodsStockService::transferIn($showroomA, $order, 'A1', 'BLK', 'M', 10, $admin->id);
    FinishedGoodsStockService::transferIn($showroomB, $order, 'A1', 'BLK', 'M', 7, $admin->id);

    actingAsRole('Showroom Staff', ['location_id' => $showroomA->id]);

    $response = $this->getJson('/api/v1/finished-goods/stock?'.http_build_query(['location_id' => $showroomB->id]));
    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.location_id', $showroomA->id);
});
