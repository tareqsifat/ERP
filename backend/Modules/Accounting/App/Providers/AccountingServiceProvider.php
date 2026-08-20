<?php

namespace Modules\Accounting\App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * sdd.md §2: this is the 1:1 backend half of the Modules/Accounting ↔
 * frontend/src/modules/accounting mapping.
 *
 * Routing is deliberately NOT wired here (no RouteServiceProvider in
 * $providers) — sdd.md §3 has backend/routes/api.php `require`
 * Modules/Accounting/routes/api.php directly instead, so keep route
 * definitions out of this provider.
 */
class AccountingServiceProvider extends ServiceProvider
{
    protected string $name = 'Accounting';

    protected string $nameLower = 'accounting';
}
