<?php

namespace Modules\Location\App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * sdd.md §2: this is the 1:1 backend half of the Modules/Location ↔
 * frontend/src/modules/location mapping.
 *
 * Routing is deliberately NOT wired here (no RouteServiceProvider in
 * $providers) — sdd.md §3 has backend/routes/api.php `require`
 * Modules/Location/routes/api.php directly instead, so keep route
 * definitions out of this provider.
 */
class LocationServiceProvider extends ServiceProvider
{
    protected string $name = 'Location';

    protected string $nameLower = 'location';
}
