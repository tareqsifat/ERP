<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Applied to every test file under tests/ AND every module's own
| tests/Feature + tests/Unit directories (sdd.md §6: "one Pest/PHPUnit
| feature test file per module").
|
*/

uses(TestCase::class, RefreshDatabase::class)
    ->beforeEach(function () {
        // Passport needs a real RSA key pair on disk to sign/verify JWTs.
        // Generated once per test run (not committed — failed_doc.md §8)
        // and reused across tests since RefreshDatabase only resets the DB.
        if (! file_exists(storage_path('oauth-private.key'))) {
            Artisan::call('passport:keys', ['--force' => true]);
        }
    })
    ->in('Feature', 'Unit', '../Modules/*/tests/Feature', '../Modules/*/tests/Unit');

/*
|--------------------------------------------------------------------------
| Shared Expectations & Helpers
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOneOf', function (array $values) {
    return expect($values)->toContain($this->value);
});

/**
 * Creates a real Passport password-grant client and points
 * services.passport.password_client_* at it, so
 * Modules/Auth/AuthController's server-side grant dispatch has a real
 * client to authenticate against.
 */
function seedPasswordGrantClient(): \Laravel\Passport\Client
{
    $client = \Laravel\Passport\Client::factory()->asPasswordClient()->create();

    config([
        'services.passport.password_client_id' => $client->getKey(),
        'services.passport.password_client_secret' => $client->plainSecret,
    ]);

    return $client;
}

/**
 * Seeds roles/permissions and returns an authenticated User with the
 * given role, for use across every module's feature tests.
 */
function actingAsRole(string $role, array $attributes = []): \App\Models\User
{
    $this_test = test();
    $this_test->seed(\Database\Seeders\PermissionSeeder::class);
    $this_test->seed(\Database\Seeders\RoleSeeder::class);

    $user = \App\Models\User::factory()->create($attributes);
    $user->assignRole($role);

    $this_test->actingAs($user, 'api');

    return $user;
}
