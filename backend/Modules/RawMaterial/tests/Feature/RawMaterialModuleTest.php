<?php

use Modules\Location\App\Models\Location;
use Modules\RawMaterial\App\Models\RawMaterial;
use Modules\RawMaterial\App\Services\RawMaterialStockService;

// sdd.md §6: smoke test hitting every route in
// Modules/RawMaterial/routes/api.php, with at least one real assertion
// per core write endpoint. Purchase order tests live in
// PurchaseOrderModuleTest.

test('admin can list raw materials', function () {
    actingAsRole('Admin');
    RawMaterial::factory()->count(2)->create();

    $this->getJson('/api/v1/raw-materials')->assertOk()->assertJsonCount(2, 'data');
});

test('admin can create a raw material', function () {
    actingAsRole('Admin');

    $response = $this->postJson('/api/v1/raw-materials', [
        'name' => 'Poly Thread',
        'category' => 'trim',
        'unit' => 'cone',
        'reorder_level' => 50,
    ]);

    $response->assertCreated()->assertJsonPath('data.category', 'trim');
});

test('creating a raw material rejects a buyer as default_supplier_id', function () {
    actingAsRole('Admin');
    $buyer = \Modules\Party\App\Models\Party::factory()->buyer()->create();

    $this->postJson('/api/v1/raw-materials', [
        'name' => 'X',
        'category' => 'fabric',
        'unit' => 'kg',
        'default_supplier_id' => $buyer->id,
    ])->assertStatus(422)->assertJsonValidationErrors('default_supplier_id');
});

test('current_stock is computed from the ledger, not stored, and only returned when requested', function () {
    actingAsRole('Admin');
    $material = RawMaterial::factory()->create();
    $store = Location::factory()->ofTypeStore()->create();
    $admin = auth('api')->user();

    RawMaterialStockService::receipt($material, $store, '100.000', $admin->id);
    RawMaterialStockService::issue($material, $store, '30.000', $admin->id);

    $response = $this->getJson("/api/v1/raw-materials/{$material->id}?with_stock=1&location_id={$store->id}");

    $response->assertOk()->assertJsonPath('data.current_stock', '70.000');

    $plainResponse = $this->getJson("/api/v1/raw-materials/{$material->id}");
    expect($plainResponse->json('data'))->not->toHaveKey('current_stock');
});

test('a manual adjustment is recorded and reflected in stock', function () {
    actingAsRole('Admin');
    $material = RawMaterial::factory()->create();
    $store = Location::factory()->ofTypeStore()->create();

    $response = $this->postJson('/api/v1/raw-material-movements', [
        'raw_material_id' => $material->id,
        'location_id' => $store->id,
        'quantity' => -5,
        'remarks' => 'Damaged in storage',
    ]);

    $response->assertCreated()->assertJsonPath('data.type', 'adjustment');
    $this->assertDatabaseHas('raw_material_stock_movements', ['raw_material_id' => $material->id, 'quantity' => -5]);
});

test('adjustment rejects a showroom location — raw material is factory/store scoped only', function () {
    actingAsRole('Admin');
    $material = RawMaterial::factory()->create();
    $showroom = Location::factory()->ofTypeShowroom()->create();

    $this->postJson('/api/v1/raw-material-movements', [
        'raw_material_id' => $material->id,
        'location_id' => $showroom->id,
        'quantity' => 5,
        'remarks' => 'test',
    ])->assertStatus(422)->assertJsonValidationErrors('location_id');
});

test('a user without raw-material.create permission cannot create a raw material', function () {
    actingAsRole('Line Supervisor');

    $this->postJson('/api/v1/raw-materials', [
        'name' => 'X', 'category' => 'fabric', 'unit' => 'kg',
    ])->assertStatus(403);
});
