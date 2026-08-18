<?php

namespace Modules\Report\App\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * sdd.md §2: this is the 1:1 backend half of the Modules/Report ↔
 * frontend/src/modules/report mapping.
 *
 * Routing is deliberately NOT wired here (no RouteServiceProvider in
 * $providers) — sdd.md §3 has backend/routes/api.php `require`
 * Modules/Report/routes/api.php directly instead, so keep route
 * definitions out of this provider.
 */
class ReportServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Report';

    protected string $nameLower = 'report';
}
