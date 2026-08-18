<?php

namespace Modules\Booking\App\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * sdd.md §2: this is the 1:1 backend half of the Modules/Booking ↔
 * frontend/src/modules/booking mapping.
 *
 * Routing is deliberately NOT wired here (no RouteServiceProvider in
 * $providers) — sdd.md §3 has backend/routes/api.php `require`
 * Modules/Booking/routes/api.php directly instead, so keep route
 * definitions out of this provider.
 */
class BookingServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Booking';

    protected string $nameLower = 'booking';
}
