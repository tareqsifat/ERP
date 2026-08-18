<?php

use App\Models\User;

// sdd.md §6: smoke test hitting every route in Modules/User/routes/api.php,
// with at least one real assertion per core write endpoint.

test('admin can list users', function () {
    actingAsRole('Admin');
    User::factory()->count(3)->create();

    $this->getJson('/api/v1/users')->assertOk()->assertJsonCount(4, 'data'); // +1 for the acting admin
});

test('admin can create a user with a role', function () {
    actingAsRole('Admin');

    $response = $this->postJson('/api/v1/users', [
        'name' => 'New Merchandiser',
        'email' => 'newmerch@example.com',
        'password' => 'password123',
        'role' => 'Merchandiser',
    ]);

    $response->assertCreated()->assertJsonPath('data.roles.0', 'Merchandiser');

    $this->assertDatabaseHas('users', ['email' => 'newmerch@example.com']);
});

test('creating a user rejects an unknown role', function () {
    actingAsRole('Admin');

    $this->postJson('/api/v1/users', [
        'name' => 'X',
        'email' => 'x@example.com',
        'password' => 'password123',
        'role' => 'Not A Real Role',
    ])->assertStatus(422)->assertJsonValidationErrors('role');
});

test('a non-admin cannot create a user', function () {
    actingAsRole('Merchandiser');

    $this->postJson('/api/v1/users', [
        'name' => 'X',
        'email' => 'x2@example.com',
        'password' => 'password123',
        'role' => 'Merchandiser',
    ])->assertStatus(403);
});

test('admin can update a user and change their role', function () {
    actingAsRole('Admin');
    $target = User::factory()->create();
    $target->assignRole('Merchandiser');

    $this->putJson("/api/v1/users/{$target->id}", [
        'name' => 'Renamed',
        'role' => 'Accountant',
    ])->assertOk()->assertJsonPath('data.roles.0', 'Accountant');
});

test('a user can update their own profile but cannot smuggle a role change through it', function () {
    $user = actingAsRole('Merchandiser');

    $response = $this->patchJson('/api/v1/users/me', [
        'name' => 'Self Updated',
        'role' => 'Admin', // not a real field on UpdateProfileRequest — must be silently ignored
    ]);

    $response->assertOk()->assertJsonPath('data.name', 'Self Updated');

    expect($user->fresh()->hasRole('Admin'))->toBeFalse();
});

test('admin can soft-delete a user', function () {
    actingAsRole('Admin');
    $target = User::factory()->create();

    $this->deleteJson("/api/v1/users/{$target->id}")->assertStatus(204);

    $this->assertSoftDeleted('users', ['id' => $target->id]);
});
