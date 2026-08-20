<?php

namespace Modules\Hrm\App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * sdd.md §2: this is the 1:1 backend half of the Modules/Hrm ↔
 * frontend/src/modules/hrm mapping.
 *
 * Routing is deliberately NOT wired here (no RouteServiceProvider in
 * $providers) — sdd.md §3 has backend/routes/api.php `require`
 * Modules/Hrm/routes/api.php directly instead, so keep route
 * definitions out of this provider.
 */
class HrmServiceProvider extends ServiceProvider
{
    protected string $name = 'Hrm';

    protected string $nameLower = 'hrm';
}
