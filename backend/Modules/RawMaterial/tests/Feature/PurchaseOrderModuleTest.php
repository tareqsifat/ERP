<?php

use Modules\Location\App\Models\Location;
use Modules\Party\App\Models\Party;
use Modules\RawMaterial\App\Models\RawMaterial;
use Modules\RawMaterial\App\Models\RawMaterialPurchaseOrder;

// sdd.md §6: smoke test covering the PO create + receive flow, the part
// of Modules/RawMaterial/routes/api.php not exercised by
// RawMaterialModuleTest.

test('admin can create a purchase order with auto-generated po_no', function () {
    actingAsRole('Admin');
    $supplier = Party::factory()->supplier()->create();
    $store = Location::factory()->ofTypeStore()->create();
    $material = RawMaterial::factory()->create();

    $response = $this->postJson('/api/v1/raw-material-purchase-orders', [
        'supplier_id' => $supplier->id,
        'location_id' => $store->id,
        'order_date' => now()->toDateString(),
        'items' => [
            ['raw_material_id' => $material->id, 'quantity_ordered' => 100, 'unit_price' => 2.5],
        ],
    ]);

    $response->assertCreated();
    $year = now()->year;
    expect($response->json('data.po_no'))->toBe("PO-{$year}-0001");
    expect($response->json('data.status'))->toBe('ordered');
    $this->assertDatabaseHas('raw_material_purchase_order_items', ['quantity_ordered' => 100, 'total_price' => 250]);
});

test('receiving a purchase order posts a receipt movement and updates stock', function () {
    actingAsRole('Admin');
    $supplier = Party::factory()->supplier()->create();
    $store = Location::factory()->ofTypeStore()->create();
    $material = RawMaterial::factory()->create();

    $po = $this->postJson('/api/v1/raw-material-purchase-orders', [
        'supplier_id' => $supplier->id,
        'location_id' => $store->id,
        'order_date' => now()->toDateString(),
        'items' => [
            ['raw_material_id' => $material->id, 'quantity_ordered' => 100, 'unit_price' => 2.5],
        ],
    ])->json('data');

    $itemId = $po['items'][0]['id'];

    $response = $this->postJson("/api/v1/raw-material-purchase-orders/{$po['id']}/receive", [
        'items' => [['item_id' => $itemId, 'quantity' => 100]],
    ]);

    $response->assertOk()->assertJsonPath('data.status', 'received');
    $this->assertDatabaseHas('raw_material_stock_movements', [
        'raw_material_id' => $material->id,
        'location_id' => $store->id,
        'type' => 'receipt',
        'quantity' => 100,
    ]);
});

test('partial receipt leaves the PO partially_received and rejects over-receipt', function () {
    actingAsRole('Admin');
    $supplier = Party::factory()->supplier()->create();
    $store = Location::factory()->ofTypeStore()->create();
    $material = RawMaterial::factory()->create();

    $po = $this->postJson('/api/v1/raw-material-purchase-orders', [
        'supplier_id' => $supplier->id,
        'location_id' => $store->id,
        'order_date' => now()->toDateString(),
        'items' => [
            ['raw_material_id' => $material->id, 'quantity_ordered' => 100, 'unit_price' => 2.5],
        ],
    ])->json('data');
    $itemId = $po['items'][0]['id'];

    $partial = $this->postJson("/api/v1/raw-material-purchase-orders/{$po['id']}/receive", [
        'items' => [['item_id' => $itemId, 'quantity' => 40]],
    ]);
    $partial->assertOk()->assertJsonPath('data.status', 'partially_received');

    $this->postJson("/api/v1/raw-material-purchase-orders/{$po['id']}/receive", [
        'items' => [['item_id' => $itemId, 'quantity' => 90]], // only 60 outstanding
    ])->assertStatus(422);
});

test('a user without purchase-order permission cannot create a PO', function () {
    actingAsRole('Line Supervisor');
    $supplier = Party::factory()->supplier()->create();
    $store = Location::factory()->ofTypeStore()->create();
    $material = RawMaterial::factory()->create();

    $this->postJson('/api/v1/raw-material-purchase-orders', [
        'supplier_id' => $supplier->id,
        'location_id' => $store->id,
        'order_date' => now()->toDateString(),
        'items' => [['raw_material_id' => $material->id, 'quantity_ordered' => 1, 'unit_price' => 1]],
    ])->assertStatus(403);
});
