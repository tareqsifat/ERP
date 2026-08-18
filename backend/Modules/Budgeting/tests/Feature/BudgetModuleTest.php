<?php

use Modules\Budgeting\App\Models\Budget;
use Modules\Order\App\Models\Order;

// sdd.md §6: smoke test hitting every route in Modules/Budgeting/routes/api.php,
// with at least one real assertion per core write endpoint.

test('admin can list budgets', function () {
    actingAsRole('Admin');
    Budget::factory()->count(2)->create();

    $this->getJson('/api/v1/budgets')->assertOk()->assertJsonCount(2, 'data');
});

test('creating a budget computes total_value server-side, ignoring a client-sent one', function () {
    actingAsRole('Admin');
    $order = Order::factory()->create();

    $response = $this->postJson('/api/v1/budgets', [
        'order_id' => $order->id,
        'style' => 'ST-1',
        'budgeted_quantity' => 1000,
        'average_unit_price' => 4.5,
        'total_value' => 1,
    ]);

    $response->assertCreated()->assertJsonPath('data.total_value', '4500.00');
});

test('a user without budgeting.create permission cannot create a budget', function () {
    actingAsRole('Line Supervisor');
    $order = Order::factory()->create();

    $this->postJson('/api/v1/budgets', [
        'order_id' => $order->id,
        'budgeted_quantity' => 100,
        'average_unit_price' => 1,
    ])->assertStatus(403);
});

test('updating budgeted_quantity recomputes total_value', function () {
    actingAsRole('Admin');
    $budget = Budget::factory()->create(['budgeted_quantity' => 100, 'average_unit_price' => 2, 'total_value' => 200]);

    $this->putJson("/api/v1/budgets/{$budget->id}", [
        'budgeted_quantity' => 200,
    ])->assertOk()->assertJsonPath('data.total_value', '400.00');
});

test('admin can soft-delete a budget', function () {
    actingAsRole('Admin');
    $budget = Budget::factory()->create();

    $this->deleteJson("/api/v1/budgets/{$budget->id}")->assertStatus(204);

    $this->assertSoftDeleted('budgets', ['id' => $budget->id]);
});
