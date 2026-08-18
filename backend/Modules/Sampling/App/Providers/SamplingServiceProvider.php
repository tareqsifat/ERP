<?php

namespace Modules\Sampling\App\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * sdd.md §2: this is the 1:1 backend half of the Modules/Sampling ↔
 * frontend/src/modules/sampling mapping.
 *
 * Routing is deliberately NOT wired here (no RouteServiceProvider in
 * $providers) — sdd.md §3 has backend/routes/api.php `require`
 * Modules/Sampling/routes/api.php directly instead, so keep route
 * definitions out of this provider.
 */
class SamplingServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Sampling';

    protected string $nameLower = 'sampling';
}
