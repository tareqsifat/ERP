<?php

namespace Modules\FinishedGoods\App\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * sdd.md §2: this is the 1:1 backend half of the Modules/FinishedGoods ↔
 * frontend/src/modules/finished-goods mapping.
 *
 * Routing is deliberately NOT wired here (no RouteServiceProvider in
 * $providers) — sdd.md §3 has backend/routes/api.php `require`
 * Modules/FinishedGoods/routes/api.php directly instead, so keep route
 * definitions out of this provider.
 */
class FinishedGoodsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'FinishedGoods';

    protected string $nameLower = 'finishedgoods';
}
