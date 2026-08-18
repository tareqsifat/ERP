<?php

use Modules\Order\App\Models\Order;
use Modules\Production\App\Models\Bundle;
use Modules\Production\App\Models\CutTicket;
use Modules\Production\App\Models\PieceSerial;

// PRD v1 §3.14/§4.13 — Reports section. Every endpoint here is a
// read-only aggregate over other modules' data; these tests assert the
// permission gate and that each report type returns a sane shape,
// not the underlying business logic (already covered by each owning
// module's own tests).

test('report.view holders can list report types and pull each report', function () {
    actingAsRole('Admin');

    $this->getJson('/api/v1/reports')->assertOk()->assertJsonCount(7, 'data.types');

    Order::factory()->count(2)->create();
    $this->getJson('/api/v1/reports/sales-orders')->assertOk()->assertJsonPath('data.total_orders', 2);

    $this->getJson('/api/v1/reports/production')->assertOk()->assertJsonStructure(['data' => ['cut_tickets_count', 'pieces_by_status']]);
    $this->getJson('/api/v1/reports/stock')->assertOk()->assertJsonStructure(['data' => ['raw_materials', 'finished_goods']]);
    $this->getJson('/api/v1/reports/subcontract')->assertOk()->assertJsonStructure(['data' => ['orders_by_direction_status', 'job_work_income']]);
    $this->getJson('/api/v1/reports/party-ledger')->assertOk();
    $this->getJson('/api/v1/reports/cashbook')->assertOk()->assertJsonStructure(['meta' => ['cash_in_hand']]);
});

test('a user without report.view is denied every report endpoint', function () {
    actingAsRole('Line Supervisor');

    $this->getJson('/api/v1/reports')->assertStatus(403);
    $this->getJson('/api/v1/reports/sales-orders')->assertStatus(403);
    $this->getJson('/api/v1/reports/traceability/ANY-SERIAL')->assertStatus(403);
});

test('traceability lookup walks the full chain for a known serial', function () {
    actingAsRole('Admin');
    $cutTicket = CutTicket::factory()->create(['style' => 'A1', 'color' => 'BLK', 'size' => 'M']);
    $bundle = Bundle::factory()->create(['cut_ticket_id' => $cutTicket->id]);
    $piece = PieceSerial::factory()->create([
        'bundle_id' => $bundle->id,
        'order_id' => $cutTicket->order_id,
        'serial' => 'TRACE-SERIAL-001',
    ]);

    $response = $this->getJson('/api/v1/reports/traceability/TRACE-SERIAL-001')->assertOk();

    $response->assertJsonPath('data.serial', 'TRACE-SERIAL-001')
        ->assertJsonPath('data.cut_ticket.style', 'A1')
        ->assertJsonPath('data.bundle.id', $bundle->id);
});

test('traceability lookup 404s for an unknown serial', function () {
    actingAsRole('Admin');

    $this->getJson('/api/v1/reports/traceability/NOPE')->assertStatus(404);
});
