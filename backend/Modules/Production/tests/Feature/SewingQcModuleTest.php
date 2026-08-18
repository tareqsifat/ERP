<?php

use Modules\FinishedGoods\App\Services\FinishedGoodsStockService;
use Modules\Location\App\Models\Location;
use Modules\Production\App\Models\Bundle;
use Modules\Production\App\Models\CutTicket;
use Modules\Production\App\Models\Line;
use Modules\Production\App\Models\PieceSerial;

// sdd.md §6: Sewing line input/output + QC pass/reject — the second
// half of the traceability spine (finalize's output is exercised in
// CuttingModuleTest). Full chain covered end-to-end in
// tests/Feature/TraceabilityTest.php.

test('assigning a bundle to a line moves it and its pieces to in_sewing, then logging output moves them to sewn', function () {
    actingAsRole('Admin');
    $bundle = Bundle::factory()->create(['status' => 'cut']);
    PieceSerial::factory()->count(3)->create(['bundle_id' => $bundle->id, 'status' => 'cut']);
    $line = Line::factory()->create();

    $this->postJson("/api/v1/bundles/{$bundle->id}/assign-to-line", ['line_id' => $line->id])
        ->assertOk()->assertJsonPath('data.status', 'in_sewing');

    expect(PieceSerial::where('bundle_id', $bundle->id)->pluck('status')->unique()->all())->toBe(['in_sewing']);

    $this->postJson("/api/v1/bundles/{$bundle->id}/log-output")
        ->assertOk()->assertJsonPath('data.status', 'sewn');

    expect(PieceSerial::where('bundle_id', $bundle->id)->pluck('status')->unique()->all())->toBe(['sewn']);
});

test('a bundle not yet cut cannot log output before being assigned to a line', function () {
    actingAsRole('Admin');
    $bundle = Bundle::factory()->create(['status' => 'cut']);

    $this->postJson("/api/v1/bundles/{$bundle->id}/log-output")->assertStatus(422);
});

test('QC pass moves the piece to finished_goods and posts a qc_intake movement at the given store', function () {
    actingAsRole('Admin');
    $cutTicket = CutTicket::factory()->create(['style' => 'A1', 'color' => 'BLK', 'size' => 'M']);
    $bundle = Bundle::factory()->create(['cut_ticket_id' => $cutTicket->id, 'status' => 'sewn']);
    $piece = PieceSerial::factory()->create([
        'bundle_id' => $bundle->id,
        'order_id' => $cutTicket->order_id,
        'status' => 'sewn',
    ]);
    $store = Location::factory()->ofTypeStore()->create();

    $response = $this->postJson("/api/v1/piece-serials/{$piece->id}/qc", [
        'result' => 'pass',
        'location_id' => $store->id,
    ]);

    $response->assertOk()->assertJsonPath('data.status', 'finished_goods');

    expect(FinishedGoodsStockService::stockOf($store, $cutTicket->order, 'A1', 'BLK', 'M'))->toBe(1);
});

test('QC reject requires a reason and does not touch Finished Goods', function () {
    actingAsRole('Admin');
    $piece = PieceSerial::factory()->create(['status' => 'sewn']);

    $this->postJson("/api/v1/piece-serials/{$piece->id}/qc", ['result' => 'reject'])
        ->assertStatus(422); // missing reason

    $response = $this->postJson("/api/v1/piece-serials/{$piece->id}/qc", [
        'result' => 'reject',
        'reason' => 'Broken stitch',
    ]);

    $response->assertOk()->assertJsonPath('data.status', 'qc_rejected')
        ->assertJsonPath('data.qc_reject_reason', 'Broken stitch');
});

test('a piece cannot be QC-ed twice (idempotency guard)', function () {
    actingAsRole('Admin');
    $piece = PieceSerial::factory()->create(['status' => 'qc_passed']);

    $this->postJson("/api/v1/piece-serials/{$piece->id}/qc", [
        'result' => 'reject',
        'reason' => 'x',
    ])->assertStatus(422);
});

test('piece serials can be searched by exact serial for traceability lookup', function () {
    actingAsRole('Admin');
    $piece = PieceSerial::factory()->create(['serial' => 'UNIQUE-SERIAL-001']);
    PieceSerial::factory()->count(2)->create();

    $this->getJson('/api/v1/piece-serials?serial=UNIQUE-SERIAL-001')
        ->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $piece->id);
});
