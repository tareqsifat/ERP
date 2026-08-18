<?php

namespace Modules\Budgeting\App\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * sdd.md §2: this is the 1:1 backend half of the Modules/Budgeting ↔
 * frontend/src/modules/budgeting mapping.
 *
 * Routing is deliberately NOT wired here (no RouteServiceProvider in
 * $providers) — sdd.md §3 has backend/routes/api.php `require`
 * Modules/Budgeting/routes/api.php directly instead, so keep route
 * definitions out of this provider.
 */
class BudgetingServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Budgeting';

    protected string $nameLower = 'budgeting';
}
