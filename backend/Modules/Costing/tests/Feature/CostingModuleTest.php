<?php

use Modules\Costing\App\Models\Costing;
use Modules\Order\App\Models\Order;

// sdd.md §6: smoke test hitting every route in Modules/Costing/routes/api.php,
// with at least one real assertion per core write endpoint.

test('admin can list costings', function () {
    actingAsRole('Admin');
    Costing::factory()->count(2)->create();

    $this->getJson('/api/v1/costings')->assertOk()->assertJsonCount(2, 'data');
});

test('creating a costing computes total_cost server-side, ignoring a client-sent one', function () {
    actingAsRole('Admin');
    $order = Order::factory()->create();

    $response = $this->postJson('/api/v1/costings', [
        'order_id' => $order->id,
        'style' => 'ST-1',
        'costed_quantity' => 1000,
        'average_unit_cost' => 3.2,
        'total_cost' => 1,
    ]);

    $response->assertCreated()->assertJsonPath('data.total_cost', '3200.00');
});

test('a user without costing.create permission cannot create a costing', function () {
    actingAsRole('Merchandiser');
    $order = Order::factory()->create();

    $this->postJson('/api/v1/costings', [
        'order_id' => $order->id,
        'costed_quantity' => 100,
        'average_unit_cost' => 1,
    ])->assertStatus(403);
});

test('updating average_unit_cost recomputes total_cost', function () {
    actingAsRole('Admin');
    $costing = Costing::factory()->create(['costed_quantity' => 100, 'average_unit_cost' => 2, 'total_cost' => 200]);

    $this->putJson("/api/v1/costings/{$costing->id}", [
        'average_unit_cost' => 5,
    ])->assertOk()->assertJsonPath('data.total_cost', '500.00');
});

test('admin can soft-delete a costing', function () {
    actingAsRole('Admin');
    $costing = Costing::factory()->create();

    $this->deleteJson("/api/v1/costings/{$costing->id}")->assertStatus(204);

    $this->assertSoftDeleted('costings', ['id' => $costing->id]);
});
