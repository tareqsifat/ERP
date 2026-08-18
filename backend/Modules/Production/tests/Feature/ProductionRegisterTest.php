<?php

use Modules\Production\App\Models\Line;
use Modules\Production\App\Models\Machine;

// sdd.md §6: Machine/Line register CRUD (the `machine.*` permission
// family, gated per todo.md Phase 4).

test('admin can create a line and assign a machine to it', function () {
    actingAsRole('Admin');

    $line = $this->postJson('/api/v1/lines', ['name' => 'Line 1', 'capacity' => 40])
        ->assertCreated()->json('data');

    $response = $this->postJson('/api/v1/machines', [
        'tag' => 'M-0001',
        'type' => 'overlock',
        'line_id' => $line['id'],
    ]);

    $response->assertCreated()->assertJsonPath('data.line_id', $line['id']);
});

test('a user without machine.create permission cannot create a line', function () {
    actingAsRole('Line Supervisor');

    $this->postJson('/api/v1/lines', ['name' => 'Line 9'])->assertStatus(403);
});

test('admin can list and filter machines by line', function () {
    actingAsRole('Admin');
    $line = Line::factory()->create();
    Machine::factory()->count(2)->create(['line_id' => $line->id]);
    Machine::factory()->count(3)->create(['line_id' => null]);

    $this->getJson("/api/v1/machines?line_id={$line->id}")->assertOk()->assertJsonCount(2, 'data');
});
