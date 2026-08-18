<?php

use App\Models\User;
use Modules\FinishedGoods\App\Services\FinishedGoodsStockService;
use Modules\Location\App\Models\Location;
use Modules\Order\App\Models\Order;
use Modules\Production\App\Models\PieceSerial;
use Modules\RawMaterial\App\Models\RawMaterial;

/**
 * sdd.md §6 / todo.md Phase 4: "create an order → cut ticket → confirm
 * serials generated → move through sewing → QC pass → assert it appears
 * in Finished Goods Inventory with the serial chain intact" — then
 * (Phase 4's manual-walkthrough item) transfer to a showroom and confirm
 * receipt. This is called out as "the single most important test in the
 * whole project" — every module the traceability spine touches
 * (Order, RawMaterial, Production, FinishedGoods, Location/StockTransfer)
 * is exercised here in one continuous chain, through the real HTTP API,
 * not by calling services directly.
 */
test('a piece is traceable end-to-end: order -> cut ticket -> bundle -> serial -> sewing -> QC -> finished goods -> stock transfer', function () {
    actingAsRole('Admin');

    $order = Order::factory()->create();
    $factory = Location::factory()->ofTypeFactory()->create();
    $mainStore = Location::factory()->ofTypeStore()->create();
    $showroom = Location::factory()->ofTypeShowroom()->create();
    $material = RawMaterial::factory()->create();
    $cuttingMaster = User::factory()->create();

    // 1. Cut Ticket created (draft — no stock/serial impact yet).
    $ticket = $this->postJson('/api/v1/cut-tickets', [
        'order_id' => $order->id,
        'style' => 'A1',
        'color' => 'BLK',
        'size' => 'M',
        'cut_date' => now()->toDateString(),
        'cutting_master_id' => $cuttingMaster->id,
        'raw_material_id' => $material->id,
        'fabric_consumed' => 6,
        'location_id' => $factory->id,
        'bundle_size' => 5,
        'planned_quantity' => 5, // one bundle, one piece we'll follow explicitly
    ])->assertCreated()->json('data');

    expect($ticket['status'])->toBe('draft');
    $this->assertDatabaseCount('bundles', 0);
    $this->assertDatabaseCount('piece_serials', 0);

    // 2. Finalize -> fabric deducted, bundle + serials generated.
    $finalized = $this->postJson("/api/v1/cut-tickets/{$ticket['id']}/finalize")
        ->assertOk()->json('data');

    expect($finalized['status'])->toBe('finalized');
    $bundle = $finalized['bundles'][0];
    expect($bundle['piece_serials'])->toHaveCount(5);

    $piece = $bundle['piece_serials'][0];
    expect($piece['status'])->toBe('cut');
    expect($piece['serial'])->toContain('A1')->toContain('BLK');

    $this->assertDatabaseHas('raw_material_stock_movements', [
        'raw_material_id' => $material->id,
        'location_id' => $factory->id,
        'type' => 'issue',
        'quantity' => -6,
    ]);

    // 3. Sewing: assign the bundle to a line, then log output.
    $line = $this->postJson('/api/v1/lines', ['name' => 'Line 1'])->assertCreated()->json('data');

    $this->postJson("/api/v1/bundles/{$bundle['id']}/assign-to-line", ['line_id' => $line['id']])
        ->assertOk()->assertJsonPath('data.status', 'in_sewing');

    expect(PieceSerial::find($piece['id'])->status)->toBe('in_sewing');

    $this->postJson("/api/v1/bundles/{$bundle['id']}/log-output")
        ->assertOk()->assertJsonPath('data.status', 'sewn');

    expect(PieceSerial::find($piece['id'])->status)->toBe('sewn');

    // 4. QC pass -> intake into Finished Goods at the Main Store,
    // closing the loop from cut piece to finished unit (PRD v2 §3.18).
    $this->postJson("/api/v1/piece-serials/{$piece['id']}/qc", [
        'result' => 'pass',
        'location_id' => $mainStore->id,
    ])->assertOk()->assertJsonPath('data.status', 'finished_goods');

    expect(FinishedGoodsStockService::stockOf($mainStore, $order, 'A1', 'BLK', 'M'))->toBe(5);

    $stockResponse = $this->getJson('/api/v1/finished-goods/stock?'.http_build_query(['location_id' => $mainStore->id]))
        ->assertOk()->json('data');
    expect($stockResponse[0]['quantity'])->toBe(5);

    $this->assertDatabaseHas('finished_goods_movements', [
        'piece_serial_id' => $piece['id'],
        'location_id' => $mainStore->id,
        'type' => 'qc_intake',
        'quantity' => 1,
    ]);

    // 5. Stock Transfer: Main Store -> Showroom, dispatch then receive —
    // the serial chain survives the move (same order/style/color/size
    // ledger key, now credited at the showroom instead).
    $transfer = $this->postJson('/api/v1/stock-transfers', [
        'from_location_id' => $mainStore->id,
        'to_location_id' => $showroom->id,
        'order_id' => $order->id,
        'style' => 'A1',
        'color' => 'BLK',
        'size' => 'M',
        'quantity' => 5,
    ])->assertCreated()->json('data');

    expect(FinishedGoodsStockService::stockOf($mainStore, $order, 'A1', 'BLK', 'M'))->toBe(0);

    $this->postJson("/api/v1/stock-transfers/{$transfer['id']}/receive", ['quantity_received' => 5])
        ->assertOk()->assertJsonPath('data.status', 'received');

    expect(FinishedGoodsStockService::stockOf($showroom, $order, 'A1', 'BLK', 'M'))->toBe(5);

    // Full circle: the exact serial we followed is still resolvable by
    // its unique code, and its status reflects the whole journey.
    $lookup = $this->getJson('/api/v1/piece-serials?'.http_build_query(['serial' => $piece['serial']]))
        ->assertOk()->json('data');
    expect($lookup)->toHaveCount(1);
    expect($lookup[0]['status'])->toBe('finished_goods');
});
