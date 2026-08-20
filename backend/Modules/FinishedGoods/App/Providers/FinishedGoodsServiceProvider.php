<?php

namespace Modules\FinishedGoods\App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * sdd.md §2: this is the 1:1 backend half of the Modules/FinishedGoods ↔
 * frontend/src/modules/finished-goods mapping.
 *
 * Routing is deliberately NOT wired here (no RouteServiceProvider in
 * $providers) — sdd.md §3 has backend/routes/api.php `require`
 * Modules/FinishedGoods/routes/api.php directly instead, so keep route
 * definitions out of this provider.
 */
class FinishedGoodsServiceProvider extends ServiceProvider
{
    protected string $name = 'FinishedGoods';

    protected string $nameLower = 'finishedgoods';
}
