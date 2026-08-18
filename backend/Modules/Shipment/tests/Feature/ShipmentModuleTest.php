<?php

use Modules\Order\App\Models\Order;
use Modules\Shipment\App\Models\Shipment;

// sdd.md §6: smoke test hitting every route in Modules/Shipment/routes/api.php,
// with at least one real assertion per core write endpoint.

test('admin can list shipments', function () {
    actingAsRole('Admin');
    Shipment::factory()->count(2)->create();

    $this->getJson('/api/v1/shipments')->assertOk()->assertJsonCount(2, 'data');
});

test('creating a shipment auto-generates a SHIP-YYYY-NNNN invoice number', function () {
    actingAsRole('Admin');
    $order = Order::factory()->create();

    $response = $this->postJson('/api/v1/shipments', [
        'order_id' => $order->id,
        'total_quantity' => 500,
        'total_cbm' => 12.5,
    ]);

    $response->assertCreated();
    $year = now()->year;
    expect($response->json('data.invoice_no'))->toBe("SHIP-{$year}-0001");
});

test('consecutive shipments in the same year get incrementing sequence numbers', function () {
    actingAsRole('Admin');
    $order = Order::factory()->create();
    $year = now()->year;

    $first = $this->postJson('/api/v1/shipments', ['order_id' => $order->id, 'total_quantity' => 10])->json('data.invoice_no');
    $second = $this->postJson('/api/v1/shipments', ['order_id' => $order->id, 'total_quantity' => 20])->json('data.invoice_no');

    expect($first)->toBe("SHIP-{$year}-0001");
    expect($second)->toBe("SHIP-{$year}-0002");
});

test('a user without shipment.create permission cannot create a shipment', function () {
    actingAsRole('Line Supervisor');
    $order = Order::factory()->create();

    $this->postJson('/api/v1/shipments', [
        'order_id' => $order->id,
        'total_quantity' => 10,
    ])->assertStatus(403);
});

test('creator is recorded as the authenticated user, not client-supplied', function () {
    $user = actingAsRole('Commercial');
    $order = Order::factory()->create();

    $response = $this->postJson('/api/v1/shipments', [
        'order_id' => $order->id,
        'total_quantity' => 10,
        'created_by' => 999999, // must be ignored
    ]);

    $response->assertCreated()->assertJsonPath('data.created_by', $user->id);
});

test('admin can soft-delete a shipment', function () {
    actingAsRole('Admin');
    $shipment = Shipment::factory()->create();

    $this->deleteJson("/api/v1/shipments/{$shipment->id}")->assertStatus(204);

    $this->assertSoftDeleted('shipments', ['id' => $shipment->id]);
});
