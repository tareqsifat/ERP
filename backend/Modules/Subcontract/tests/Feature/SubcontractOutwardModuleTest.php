<?php

use App\Models\User;
use Modules\Location\App\Models\Location;
use Modules\Order\App\Models\Order;
use Modules\Party\App\Models\Party;
use Modules\Production\App\Models\PieceSerial;
use Modules\RawMaterial\App\Models\RawMaterial;
use Modules\RawMaterial\App\Services\RawMaterialStockService;

// PRD v2 §3.23 — Outward Subcontract: issue work to an external party
// either by handing over already-cut pieces (no status mutation, tracked
// only via SubcontractOrderPiece) or by issuing raw material (which
// creates+finalizes a real CutTicket so subcontractor-cut pieces keep
// full traceability — see App\Services\SubcontractOutwardService).

test('creating an outward subcontract order generates a subcontract_no', function () {
    actingAsRole('Merchandiser');
    $party = Party::factory()->subcontractor()->create();
    $order = Order::factory()->create();

    $response = $this->postJson('/api/v1/subcontract-orders', [
        'direction' => 'outward',
        'party_id' => $party->id,
        'order_id' => $order->id,
        'style' => 'A1',
        'color' => 'BLK',
        'rate' => 20,
        'rate_unit' => 'piece',
        'quantity_expected' => 50,
    ]);

    $response->assertCreated();
    $year = now()->year;
    expect($response->json('data.subcontract_no'))->toBe("SC-{$year}-0001");
    expect($response->json('data.status'))->toBe('open');
});

test('issuing already-cut pieces attaches them without mutating piece status and posts an issue_value ledger entry', function () {
    actingAsRole('Merchandiser');
    $party = Party::factory()->subcontractor()->create();
    $order = Order::factory()->create();
    $pieces = PieceSerial::factory()->count(3)->create(['order_id' => $order->id, 'status' => 'sewn']);

    $scOrder = $this->postJson('/api/v1/subcontract-orders', [
        'direction' => 'outward', 'party_id' => $party->id, 'order_id' => $order->id,
        'style' => 'A1', 'rate' => 20, 'rate_unit' => 'piece', 'quantity_expected' => 3,
    ])->json('data');

    $response = $this->postJson("/api/v1/subcontract-orders/{$scOrder['id']}/issue-pieces", [
        'piece_serial_ids' => $pieces->pluck('id')->all(),
    ]);

    $response->assertOk();
    expect(PieceSerial::find($pieces->first()->id)->status)->toBe('sewn'); // untouched

    $this->assertDatabaseHas('subcontract_order_pieces', [
        'subcontract_order_id' => $scOrder['id'],
        'piece_serial_id' => $pieces->first()->id,
        'resolution' => null,
    ]);
    $this->assertDatabaseHas('subcontract_ledger_entries', [
        'subcontract_order_id' => $scOrder['id'],
        'type' => 'issue_value',
        'amount' => '60.00', // 3 pieces * 20
    ]);
});

test('issuing raw material creates and finalizes a real cut ticket and attaches its pieces', function () {
    actingAsRole('Merchandiser');
    $party = Party::factory()->subcontractor()->create();
    $order = Order::factory()->create();
    $factory = Location::factory()->ofTypeFactory()->create();
    $material = RawMaterial::factory()->create();
    RawMaterialStockService::receipt($material, $factory, '100', User::factory()->create()->id);
    $cuttingMaster = User::factory()->create();

    $scOrder = $this->postJson('/api/v1/subcontract-orders', [
        'direction' => 'outward', 'party_id' => $party->id, 'order_id' => $order->id,
        'style' => 'A1', 'color' => 'BLK', 'size' => 'M', 'rate' => 20, 'rate_unit' => 'piece',
        'quantity_expected' => 10, 'raw_material_id' => $material->id, 'raw_material_quantity' => 12,
        'location_id' => $factory->id,
    ])->json('data');

    $response = $this->postJson("/api/v1/subcontract-orders/{$scOrder['id']}/issue-raw-material", [
        'cut_date' => now()->toDateString(),
        'cutting_master_id' => $cuttingMaster->id,
        'bundle_size' => 5,
        'quantity' => 10,
    ]);

    $response->assertCreated();
    expect($response->json('data.status'))->toBe('finalized');
    expect($response->json('data.bundles.0.piece_serials'))->toHaveCount(5);

    $this->assertDatabaseHas('raw_material_stock_movements', [
        'raw_material_id' => $material->id, 'location_id' => $factory->id, 'type' => 'issue', 'quantity' => -12,
    ]);
    $this->assertDatabaseCount('subcontract_order_pieces', 10);
    $this->assertDatabaseHas('subcontract_ledger_entries', [
        'subcontract_order_id' => $scOrder['id'], 'type' => 'issue_value', 'amount' => '200.00',
    ]);
});

test('returning and writing off pieces resolves them, posts ledger entries, and closes the order', function () {
    actingAsRole('Merchandiser');
    $party = Party::factory()->subcontractor()->create();
    $order = Order::factory()->create();
    $pieces = PieceSerial::factory()->count(2)->create(['order_id' => $order->id, 'status' => 'sewn']);

    $scOrder = $this->postJson('/api/v1/subcontract-orders', [
        'direction' => 'outward', 'party_id' => $party->id, 'order_id' => $order->id,
        'style' => 'A1', 'rate' => 20, 'rate_unit' => 'piece', 'quantity_expected' => 2,
    ])->json('data');

    $this->postJson("/api/v1/subcontract-orders/{$scOrder['id']}/issue-pieces", [
        'piece_serial_ids' => $pieces->pluck('id')->all(),
    ])->assertOk();

    $response = $this->postJson("/api/v1/subcontract-orders/{$scOrder['id']}/return-pieces", [
        'returned_piece_serial_ids' => [$pieces[0]->id],
        'written_off_piece_serial_ids' => [$pieces[1]->id],
    ]);

    $response->assertOk()->assertJsonPath('data.status', 'closed');
    expect(PieceSerial::find($pieces[0]->id)->status)->toBe('sewn'); // QC-ready
    $this->assertDatabaseHas('subcontract_order_pieces', ['piece_serial_id' => $pieces[0]->id, 'resolution' => 'returned']);
    $this->assertDatabaseHas('subcontract_order_pieces', ['piece_serial_id' => $pieces[1]->id, 'resolution' => 'written_off']);
    $this->assertDatabaseHas('subcontract_ledger_entries', ['subcontract_order_id' => $scOrder['id'], 'type' => 'return_value', 'amount' => '20.00']);
    $this->assertDatabaseHas('subcontract_ledger_entries', ['subcontract_order_id' => $scOrder['id'], 'type' => 'shortage_deduction', 'amount' => '20.00']);
});

test('a user without subcontract.outward.manage cannot issue pieces', function () {
    actingAsRole('Showroom Staff');
    $party = Party::factory()->subcontractor()->create();
    $order = Order::factory()->create();
    $piece = PieceSerial::factory()->create(['order_id' => $order->id]);

    $scOrder = \Modules\Subcontract\App\Models\SubcontractOrder::factory()->outward()->create([
        'party_id' => $party->id, 'order_id' => $order->id,
    ]);

    $this->postJson("/api/v1/subcontract-orders/{$scOrder->id}/issue-pieces", [
        'piece_serial_ids' => [$piece->id],
    ])->assertStatus(403);
});
