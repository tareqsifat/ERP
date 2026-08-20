<?php

namespace Modules\Shipment\App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * sdd.md §2: this is the 1:1 backend half of the Modules/Shipment ↔
 * frontend/src/modules/shipment mapping.
 *
 * Routing is deliberately NOT wired here (no RouteServiceProvider in
 * $providers) — sdd.md §3 has backend/routes/api.php `require`
 * Modules/Shipment/routes/api.php directly instead, so keep route
 * definitions out of this provider.
 */
class ShipmentServiceProvider extends ServiceProvider
{
    protected string $name = 'Shipment';

    protected string $nameLower = 'shipment';
}
