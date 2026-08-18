<?php

use Modules\Party\App\Models\Party;

// sdd.md §6: smoke test hitting every route in Modules/Party/routes/api.php,
// with at least one real assertion per core write endpoint.

test('admin can list parties filtered by type', function () {
    actingAsRole('Admin');
    Party::factory()->buyer()->count(2)->create();
    Party::factory()->supplier()->count(1)->create();

    $this->getJson('/api/v1/parties?type=buyer')->assertOk()->assertJsonCount(2, 'data');
});

test('admin can create a subcontractor party', function () {
    actingAsRole('Admin');

    $response = $this->postJson('/api/v1/parties', [
        'name' => 'ABC Job Works',
        'type' => 'subcontractor',
        'phone' => '01700000000',
    ]);

    $response->assertCreated()->assertJsonPath('data.type', 'subcontractor');
    $this->assertDatabaseHas('parties', ['name' => 'ABC Job Works', 'type' => 'subcontractor']);
});

test('creating a party rejects an unknown type', function () {
    actingAsRole('Admin');

    $this->postJson('/api/v1/parties', [
        'name' => 'X',
        'type' => 'not-a-real-type',
    ])->assertStatus(422)->assertJsonValidationErrors('type');
});

test('a user without party.create permission cannot create a party', function () {
    actingAsRole('Line Supervisor');

    $this->postJson('/api/v1/parties', [
        'name' => 'X',
        'type' => 'buyer',
    ])->assertStatus(403);
});

test('the party create endpoint response never includes computed financial totals', function () {
    actingAsRole('Admin');

    $response = $this->postJson('/api/v1/parties', [
        'name' => 'No Ledger Yet Buyer',
        'type' => 'buyer',
    ]);

    $response->assertCreated();
    foreach (['total_bill', 'advance', 'paid', 'due', 'balance'] as $key) {
        expect($response->json('data'))->not->toHaveKey($key);
    }
});

test('admin can update a party', function () {
    actingAsRole('Admin');
    $party = Party::factory()->buyer()->create(['name' => 'Old Name']);

    $this->putJson("/api/v1/parties/{$party->id}", [
        'name' => 'New Name',
    ])->assertOk()->assertJsonPath('data.name', 'New Name');
});

test('admin can soft-delete a party', function () {
    actingAsRole('Admin');
    $party = Party::factory()->create();

    $this->deleteJson("/api/v1/parties/{$party->id}")->assertStatus(204);

    $this->assertSoftDeleted('parties', ['id' => $party->id]);
});
