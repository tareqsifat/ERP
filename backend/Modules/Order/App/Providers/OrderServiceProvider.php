<?php

namespace Modules\Order\App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * sdd.md §2: this is the 1:1 backend half of the Modules/Order ↔
 * frontend/src/modules/order mapping.
 *
 * Routing is deliberately NOT wired here (no RouteServiceProvider in
 * $providers) — sdd.md §3 has backend/routes/api.php `require`
 * Modules/Order/routes/api.php directly instead, so keep route
 * definitions out of this provider.
 */
class OrderServiceProvider extends ServiceProvider
{
    protected string $name = 'Order';

    protected string $nameLower = 'order';
}
