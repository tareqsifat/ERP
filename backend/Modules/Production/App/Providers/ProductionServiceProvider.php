<?php

namespace Modules\Production\App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * sdd.md §2: this is the 1:1 backend half of the Modules/Production ↔
 * frontend/src/modules/production mapping.
 *
 * Routing is deliberately NOT wired here (no RouteServiceProvider in
 * $providers) — sdd.md §3 has backend/routes/api.php `require`
 * Modules/Production/routes/api.php directly instead, so keep route
 * definitions out of this provider.
 */
class ProductionServiceProvider extends ServiceProvider
{
    protected string $name = 'Production';

    protected string $nameLower = 'production';
}
