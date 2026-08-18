<?php

use App\Models\User;
use Modules\Location\App\Models\Location;
use Modules\Order\App\Models\Order;
use Modules\Production\App\Models\CutTicket;
use Modules\RawMaterial\App\Models\RawMaterial;

// sdd.md §6: Cut Ticket create + finalize — the traceability spine's
// entry point. Full order→cut→sew→QC→Finished Goods chain is covered
// end-to-end in tests/Feature/TraceabilityTest.php; this file focuses on
// CuttingModuleTest-local concerns (draft editability, finalize's
// bundle/serial generation and fabric deduction, idempotency).

test('admin can create a draft cut ticket and edit it before finalizing', function () {
    actingAsRole('Admin');
    $order = Order::factory()->create();
    $factory = Location::factory()->ofTypeFactory()->create();
    $material = RawMaterial::factory()->create();
    $cuttingMaster = User::factory()->create();

    $ticket = $this->postJson('/api/v1/cut-tickets', [
        'order_id' => $order->id,
        'style' => 'A1',
        'color' => 'BLK',
        'size' => 'M',
        'cut_date' => now()->toDateString(),
        'cutting_master_id' => $cuttingMaster->id,
        'raw_material_id' => $material->id,
        'fabric_consumed' => 10.5,
        'location_id' => $factory->id,
        'bundle_size' => 20,
        'planned_quantity' => 45,
    ])->assertCreated()->json('data');

    expect($ticket['status'])->toBe('draft');

    $this->putJson("/api/v1/cut-tickets/{$ticket['id']}", ['planned_quantity' => 50])
        ->assertOk()->assertJsonPath('data.planned_quantity', 50);
});

test('finalizing a cut ticket deducts fabric, generates bundles sized to bundle_size, and unique serials per piece', function () {
    actingAsRole('Admin');
    $order = Order::factory()->create();
    $factory = Location::factory()->ofTypeFactory()->create();
    $material = RawMaterial::factory()->create();
    $cuttingMaster = User::factory()->create();

    $ticket = CutTicket::factory()->create([
        'order_id' => $order->id,
        'location_id' => $factory->id,
        'raw_material_id' => $material->id,
        'cutting_master_id' => $cuttingMaster->id,
        'bundle_size' => 20,
        'planned_quantity' => 45, // 3 bundles: 20, 20, 5
        'fabric_consumed' => 10.5,
    ]);

    $response = $this->postJson("/api/v1/cut-tickets/{$ticket->id}/finalize");

    $response->assertOk()->assertJsonPath('data.status', 'finalized');

    $bundles = $response->json('data.bundles');
    expect($bundles)->toHaveCount(3);
    expect(collect($bundles)->pluck('quantity')->all())->toBe([20, 20, 5]);

    $allSerials = collect($bundles)->flatMap(fn ($b) => collect($b['piece_serials'])->pluck('serial'));
    expect($allSerials)->toHaveCount(45);
    expect($allSerials->unique())->toHaveCount(45); // sdd.md §5: unique, indexed

    $this->assertDatabaseHas('raw_material_stock_movements', [
        'raw_material_id' => $material->id,
        'location_id' => $factory->id,
        'type' => 'issue',
        'quantity' => -10.5,
    ]);

    expect(RawMaterial::find($material->id)->stockOn($factory))->toBe('-10.500');
});

test('finalizing an already-finalized cut ticket is rejected (idempotency guard)', function () {
    actingAsRole('Admin');
    $ticket = CutTicket::factory()->create(['status' => 'finalized', 'finalized_at' => now()]);

    $this->postJson("/api/v1/cut-tickets/{$ticket->id}/finalize")->assertStatus(422);
});

test('a finalized cut ticket can no longer be edited or deleted', function () {
    actingAsRole('Admin');
    $ticket = CutTicket::factory()->create(['status' => 'finalized', 'finalized_at' => now()]);

    $this->putJson("/api/v1/cut-tickets/{$ticket->id}", ['planned_quantity' => 99])->assertStatus(422);
    $this->deleteJson("/api/v1/cut-tickets/{$ticket->id}")->assertStatus(422);
});

test('a user without production.cutting.create permission cannot create a cut ticket', function () {
    actingAsRole('Line Supervisor');
    $order = Order::factory()->create();
    $factory = Location::factory()->ofTypeFactory()->create();
    $material = RawMaterial::factory()->create();

    $this->postJson('/api/v1/cut-tickets', [
        'order_id' => $order->id,
        'style' => 'A1',
        'color' => 'BLK',
        'cut_date' => now()->toDateString(),
        'cutting_master_id' => User::factory()->create()->id,
        'raw_material_id' => $material->id,
        'fabric_consumed' => 5,
        'location_id' => $factory->id,
        'bundle_size' => 20,
        'planned_quantity' => 20,
    ])->assertStatus(403);
});
