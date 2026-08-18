<?php

use Modules\Order\App\Models\Order;
use Modules\Sampling\App\Models\Sample;

// sdd.md §6: smoke test hitting every route in Modules/Sampling/routes/api.php,
// with at least one real assertion per core write endpoint.

test('admin can list samples', function () {
    actingAsRole('Admin');
    Sample::factory()->count(2)->create();

    $this->getJson('/api/v1/samples')->assertOk()->assertJsonCount(2, 'data');
});

test('merchandiser can create a sample request', function () {
    actingAsRole('Merchandiser');
    $order = Order::factory()->create();

    $response = $this->postJson('/api/v1/samples', [
        'order_id' => $order->id,
        'style_number' => 'ST-1',
        'sample_type' => 'pp',
        'quantity' => 3,
    ]);

    $response->assertCreated()->assertJsonPath('data.status', 'requested');
    $this->assertDatabaseHas('samples', ['order_id' => $order->id, 'sample_type' => 'pp']);
});

test('creating a sample rejects an unknown sample_type', function () {
    actingAsRole('Merchandiser');
    $order = Order::factory()->create();

    $this->postJson('/api/v1/samples', [
        'order_id' => $order->id,
        'sample_type' => 'not-a-real-type',
        'quantity' => 1,
    ])->assertStatus(422)->assertJsonValidationErrors('sample_type');
});

test('a user without sampling.create permission cannot create a sample', function () {
    actingAsRole('Line Supervisor');
    $order = Order::factory()->create();

    $this->postJson('/api/v1/samples', [
        'order_id' => $order->id,
        'quantity' => 1,
    ])->assertStatus(403);
});

test('admin can update a sample status', function () {
    actingAsRole('Admin');
    $sample = Sample::factory()->create(['status' => 'requested']);

    $this->putJson("/api/v1/samples/{$sample->id}", [
        'status' => 'approved',
    ])->assertOk()->assertJsonPath('data.status', 'approved');
});

test('admin can soft-delete a sample', function () {
    actingAsRole('Admin');
    $sample = Sample::factory()->create();

    $this->deleteJson("/api/v1/samples/{$sample->id}")->assertStatus(204);

    $this->assertSoftDeleted('samples', ['id' => $sample->id]);
});
