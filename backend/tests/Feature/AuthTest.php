<?php

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

// sdd.md §6: "A dedicated AuthTest covering: login issues a token,
// protected routes reject an unauthenticated request (401), and a route
// gated by permission: rejects a user without that permission (403)."

test('login issues an access token and refresh token for valid credentials', function () {
    seedPasswordGrantClient();
    $user = User::factory()->create(['email' => 'merch@example.com']);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'merch@example.com',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.user.email', 'merch@example.com')
        ->assertJsonStructure(['data' => ['access_token', 'refresh_token', 'expires_in', 'user', 'roles', 'permissions']]);
});

test('login rejects invalid credentials without revealing whether the email exists', function () {
    seedPasswordGrantClient();
    User::factory()->create(['email' => 'merch@example.com']);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'merch@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('email');
});

test('login rejects a deactivated user', function () {
    seedPasswordGrantClient();
    User::factory()->create(['email' => 'inactive@example.com', 'is_active' => false]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'inactive@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(422);
});

test('protected routes reject an unauthenticated request with 401', function () {
    $response = $this->getJson('/api/v1/auth/me');

    $response->assertStatus(401);
});

test('a permission-gated route rejects a user without that permission with 403', function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);

    // "Line Supervisor" is granted production.sewing.* only — not user.view.
    $user = User::factory()->create();
    $user->assignRole('Line Supervisor');
    $this->actingAs($user, 'api');

    $response = $this->getJson('/api/v1/users');

    $response->assertStatus(403);
});

test('an authenticated request with the right permission succeeds', function () {
    actingAsRole('Admin');

    $response = $this->getJson('/api/v1/users');

    $response->assertOk();
});

test('logout revokes the access token so it cannot be reused', function () {
    seedPasswordGrantClient();
    User::factory()->create(['email' => 'merch@example.com']);

    $login = $this->postJson('/api/v1/auth/login', [
        'email' => 'merch@example.com',
        'password' => 'password',
    ])->json('data');

    $this->withToken($login['access_token'])
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    $this->withToken($login['access_token'])
        ->getJson('/api/v1/auth/me')
        ->assertStatus(401);
});
