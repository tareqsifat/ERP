<?php

namespace Modules\Auth\App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * sdd.md §2: this is the 1:1 backend half of the Modules/Auth ↔
 * frontend/src/modules/auth mapping.
 *
 * Routing is deliberately NOT wired here (no RouteServiceProvider in
 * $providers) — sdd.md §3 has backend/routes/api.php `require`
 * Modules/Auth/routes/api.php directly instead, so keep route
 * definitions out of this provider.
 */
class AuthServiceProvider extends ServiceProvider
{
    protected string $name = 'Auth';

    protected string $nameLower = 'auth';
}
