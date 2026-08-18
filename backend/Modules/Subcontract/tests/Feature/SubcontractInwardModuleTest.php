<?php

use App\Models\User;
use Modules\FinishedGoods\App\Services\FinishedGoodsStockService;
use Modules\Location\App\Models\Location;
use Modules\Order\App\Models\Order;
use Modules\Party\App\Models\Party;
use Modules\Production\App\Models\PieceSerial;
use Modules\RawMaterial\App\Models\RawMaterial;
use Modules\RawMaterial\App\Services\RawMaterialStockService;

// PRD v2 §3.24 — Inward Subcontract: job-work capacity sold to an
// external party. Their fabric is sewn here, tagged onto a real CutTicket
// via `inward_subcontract_order_id`; QC leaves those pieces at
// `qc_passed` instead of auto-intaking into our own Finished Goods (they
// were never ours) until SubcontractInwardService::dispatchBack() ships
// them back out (reusing PieceSerial's `shipped` status).

test('a QC-passed piece tagged to an inward job stays out of Finished Goods and dispatch-back ships it and posts job_work_income', function () {
    actingAsRole('Production');
    $party = Party::factory()->subcontractor()->create();
    $ownOrder = Order::factory()->create(); // Inward still needs a real Order to hang the Cut Ticket's serials off
    $factory = Location::factory()->ofTypeFactory()->create();
    $mainStore = Location::factory()->ofTypeStore()->create();
    $material = RawMaterial::factory()->create();
    RawMaterialStockService::receipt($material, $factory, '100', User::factory()->create()->id);
    $cuttingMaster = User::factory()->create();

    $scOrder = $this->postJson('/api/v1/subcontract-orders', [
        'direction' => 'inward', 'party_id' => $party->id,
        'style' => 'A1', 'color' => 'BLK', 'size' => 'M', 'rate' => 15, 'rate_unit' => 'piece',
        'quantity_expected' => 5,
    ])->assertCreated()->json('data');

    $ticket = $this->postJson('/api/v1/cut-tickets', [
        'order_id' => $ownOrder->id,
        'style' => 'A1', 'color' => 'BLK', 'size' => 'M',
        'cut_date' => now()->toDateString(),
        'cutting_master_id' => $cuttingMaster->id,
        'raw_material_id' => $material->id,
        'fabric_consumed' => 6,
        'location_id' => $factory->id,
        'bundle_size' => 5,
        'planned_quantity' => 5,
        'inward_subcontract_order_id' => $scOrder['id'],
    ])->assertCreated()->json('data');

    $finalized = $this->postJson("/api/v1/cut-tickets/{$ticket['id']}/finalize")->assertOk()->json('data');
    $piece = $finalized['bundles'][0]['piece_serials'][0];

    $this->postJson("/api/v1/piece-serials/{$piece['id']}/qc", [
        'result' => 'pass',
        'location_id' => $mainStore->id,
    ])->assertOk()->assertJsonPath('data.status', 'qc_passed'); // NOT finished_goods

    expect(FinishedGoodsStockService::stockOf($mainStore, $ownOrder, 'A1', 'BLK', 'M'))->toBe(0);

    $response = $this->postJson("/api/v1/subcontract-orders/{$scOrder['id']}/dispatch-back");

    $response->assertOk()->assertJsonPath('data.status', 'closed');
    expect(PieceSerial::find($piece['id'])->status)->toBe('shipped');
    expect($response->json('data.job_work_income_amount'))->toBe('15.00'); // 1 piece * 15
    $this->assertDatabaseHas('subcontract_ledger_entries', [
        'subcontract_order_id' => $scOrder['id'], 'type' => 'job_work_income', 'amount' => '15.00',
    ]);
});

test('dispatching back an inward job with no QC-passed pieces yet is rejected', function () {
    actingAsRole('Production');
    $party = Party::factory()->subcontractor()->create();

    $scOrder = \Modules\Subcontract\App\Models\SubcontractOrder::factory()->inward()->create([
        'party_id' => $party->id,
    ]);

    $this->postJson("/api/v1/subcontract-orders/{$scOrder->id}/dispatch-back")
        ->assertStatus(422);
});

test('a user without subcontract.inward.manage cannot dispatch back', function () {
    actingAsRole('Merchandiser'); // has outward.manage, not inward.manage
    $party = Party::factory()->subcontractor()->create();

    $scOrder = \Modules\Subcontract\App\Models\SubcontractOrder::factory()->inward()->create([
        'party_id' => $party->id,
    ]);

    $this->postJson("/api/v1/subcontract-orders/{$scOrder->id}/dispatch-back")
        ->assertStatus(403);
});
