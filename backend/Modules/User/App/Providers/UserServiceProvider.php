<?php

namespace Modules\User\App\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * sdd.md §2: this is the 1:1 backend half of the Modules/User ↔
 * frontend/src/modules/user mapping.
 *
 * Routing is deliberately NOT wired here (no RouteServiceProvider in
 * $providers) — sdd.md §3 has backend/routes/api.php `require`
 * Modules/User/routes/api.php directly instead, so keep route
 * definitions out of this provider.
 */
class UserServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'User';

    protected string $nameLower = 'user';
}
