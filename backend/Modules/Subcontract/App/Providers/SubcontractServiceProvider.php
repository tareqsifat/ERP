<?php

namespace Modules\Subcontract\App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * sdd.md §2: this is the 1:1 backend half of the Modules/Subcontract ↔
 * frontend/src/modules/subcontract mapping.
 *
 * Routing is deliberately NOT wired here (no RouteServiceProvider in
 * $providers) — sdd.md §3 has backend/routes/api.php `require`
 * Modules/Subcontract/routes/api.php directly instead, so keep route
 * definitions out of this provider.
 */
class SubcontractServiceProvider extends ServiceProvider
{
    protected string $name = 'Subcontract';

    protected string $nameLower = 'subcontract';
}
