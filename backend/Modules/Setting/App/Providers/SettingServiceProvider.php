<?php

namespace Modules\Setting\App\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * sdd.md §2: this is the 1:1 backend half of the Modules/Setting ↔
 * frontend/src/modules/setting mapping.
 *
 * Routing is deliberately NOT wired here (no RouteServiceProvider in
 * $providers) — sdd.md §3 has backend/routes/api.php `require`
 * Modules/Setting/routes/api.php directly instead, so keep route
 * definitions out of this provider.
 */
class SettingServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Setting';

    protected string $nameLower = 'setting';
}
