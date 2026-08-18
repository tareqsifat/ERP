<?php

use App\Models\User;
use Modules\Order\App\Models\Order;
use Modules\Party\App\Models\Party;

// sdd.md §6: smoke test hitting every route in Modules/Order/routes/api.php,
// with at least one real assertion per core write endpoint.

test('admin can list orders', function () {
    actingAsRole('Admin');
    Order::factory()->count(2)->create();

    $this->getJson('/api/v1/orders')->assertOk()->assertJsonCount(2, 'data');
});

test('creating an order auto-generates the order number and computes the grand total from line items', function () {
    actingAsRole('Admin');
    $party = Party::factory()->buyer()->create();
    $merchandiser = User::factory()->create();

    $response = $this->postJson('/api/v1/orders', [
        'party_id' => $party->id,
        'merchandiser_id' => $merchandiser->id,
        'shipment_mode' => 'sea',
        'payment_mode' => 'lc',
        'line_items' => [
            ['style' => 'ST-1', 'color' => 'Red', 'item' => 'T-Shirt', 'quantity' => 100, 'unit_price' => 5.50],
            ['style' => 'ST-2', 'color' => 'Blue', 'item' => 'Polo', 'quantity' => 50, 'unit_price' => 8.00],
        ],
    ]);

    $response->assertCreated();
    // 100*5.50 + 50*8.00 = 550 + 400 = 950
    $response->assertJsonPath('data.grand_total', '950.00');
    expect($response->json('data.order_no'))->toMatch('/^\d{7}$/');
    $this->assertDatabaseCount('order_line_items', 2);
});

test('creating an order rejects a client-sent total_price and recomputes it server-side', function () {
    actingAsRole('Admin');
    $party = Party::factory()->buyer()->create();
    $merchandiser = User::factory()->create();

    $response = $this->postJson('/api/v1/orders', [
        'party_id' => $party->id,
        'merchandiser_id' => $merchandiser->id,
        'shipment_mode' => 'sea',
        'payment_mode' => 'lc',
        'line_items' => [
            ['style' => 'ST-1', 'color' => 'Red', 'item' => 'T-Shirt', 'quantity' => 10, 'unit_price' => 2, 'total_price' => 99999],
        ],
    ]);

    $response->assertCreated()->assertJsonPath('data.grand_total', '20.00');
    $this->assertDatabaseHas('order_line_items', ['quantity' => 10, 'unit_price' => 2, 'total_price' => 20]);
});

test('creating an order rejects a supplier party as the buyer', function () {
    actingAsRole('Admin');
    $supplier = Party::factory()->supplier()->create();
    $merchandiser = User::factory()->create();

    $this->postJson('/api/v1/orders', [
        'party_id' => $supplier->id,
        'merchandiser_id' => $merchandiser->id,
        'shipment_mode' => 'sea',
        'payment_mode' => 'lc',
        'line_items' => [
            ['style' => 'ST-1', 'color' => 'Red', 'item' => 'T-Shirt', 'quantity' => 10, 'unit_price' => 2],
        ],
    ])->assertStatus(422)->assertJsonValidationErrors('party_id');
});

test('creating an order requires at least one line item', function () {
    actingAsRole('Admin');
    $party = Party::factory()->buyer()->create();
    $merchandiser = User::factory()->create();

    $this->postJson('/api/v1/orders', [
        'party_id' => $party->id,
        'merchandiser_id' => $merchandiser->id,
        'shipment_mode' => 'sea',
        'payment_mode' => 'lc',
        'line_items' => [],
    ])->assertStatus(422)->assertJsonValidationErrors('line_items');
});

test('a user without order.create permission cannot create an order', function () {
    actingAsRole('Line Supervisor');
    $party = Party::factory()->buyer()->create();
    $merchandiser = User::factory()->create();

    $this->postJson('/api/v1/orders', [
        'party_id' => $party->id,
        'merchandiser_id' => $merchandiser->id,
        'shipment_mode' => 'sea',
        'payment_mode' => 'lc',
        'line_items' => [
            ['style' => 'ST-1', 'color' => 'Red', 'item' => 'T-Shirt', 'quantity' => 10, 'unit_price' => 2],
        ],
    ])->assertStatus(403);
});

test('replacing line items on update recomputes the grand total', function () {
    actingAsRole('Admin');
    $order = Order::factory()->create();
    $order->lineItems()->create(['style' => 'OLD', 'color' => 'X', 'item' => 'X', 'quantity' => 1, 'unit_price' => 1, 'total_price' => 1]);
    $order->recalculateGrandTotal();

    $response = $this->putJson("/api/v1/orders/{$order->id}", [
        'line_items' => [
            ['style' => 'NEW', 'color' => 'Y', 'item' => 'Y', 'quantity' => 5, 'unit_price' => 10],
        ],
    ]);

    $response->assertOk()->assertJsonPath('data.grand_total', '50.00');
    $this->assertSoftDeleted('order_line_items', ['style' => 'OLD']);
});

test('admin can soft-delete an order', function () {
    actingAsRole('Admin');
    $order = Order::factory()->create();

    $this->deleteJson("/api/v1/orders/{$order->id}")->assertStatus(204);

    $this->assertSoftDeleted('orders', ['id' => $order->id]);
});
