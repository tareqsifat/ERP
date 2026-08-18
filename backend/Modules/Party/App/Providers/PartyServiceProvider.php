<?php

namespace Modules\Party\App\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * sdd.md §2: this is the 1:1 backend half of the Modules/Party ↔
 * frontend/src/modules/party mapping.
 *
 * Routing is deliberately NOT wired here (no RouteServiceProvider in
 * $providers) — sdd.md §3 has backend/routes/api.php `require`
 * Modules/Party/routes/api.php directly instead, so keep route
 * definitions out of this provider.
 */
class PartyServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Party';

    protected string $nameLower = 'party';
}
