<?php

use Modules\Location\App\Models\Location;

// sdd.md §6: smoke test hitting every route in Modules/Location/routes/api.php,
// with at least one real assertion per core write endpoint. StockTransfer
// routes (also in this module, per sdd.md §2's repo layout) are covered
// separately in StockTransferModuleTest.

test('admin can list locations filtered by type', function () {
    actingAsRole('Admin');
    Location::factory()->ofTypeShowroom()->count(3)->create();
    Location::factory()->ofTypeFactory()->count(1)->create();

    $this->getJson('/api/v1/locations?type=showroom')->assertOk()->assertJsonCount(3, 'data');
});

test('admin can create a location', function () {
    actingAsRole('Admin');

    $response = $this->postJson('/api/v1/locations', [
        'name' => 'Showroom 4',
        'type' => 'showroom',
    ]);

    $response->assertCreated()->assertJsonPath('data.type', 'showroom');
});

test('a user without location.create permission cannot create a location', function () {
    actingAsRole('Line Supervisor');

    $this->postJson('/api/v1/locations', ['name' => 'X', 'type' => 'store'])->assertStatus(403);
});

test('admin can soft-delete a location', function () {
    actingAsRole('Admin');
    $location = Location::factory()->create();

    $this->deleteJson("/api/v1/locations/{$location->id}")->assertStatus(204);

    $this->assertSoftDeleted('locations', ['id' => $location->id]);
});
