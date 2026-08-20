<?php

namespace Modules\Party\App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * sdd.md §2: this is the 1:1 backend half of the Modules/Party ↔
 * frontend/src/modules/party mapping.
 *
 * Routing is deliberately NOT wired here (no RouteServiceProvider in
 * $providers) — sdd.md §3 has backend/routes/api.php `require`
 * Modules/Party/routes/api.php directly instead, so keep route
 * definitions out of this provider.
 */
class PartyServiceProvider extends ServiceProvider
{
    protected string $name = 'Party';

    protected string $nameLower = 'party';
}
