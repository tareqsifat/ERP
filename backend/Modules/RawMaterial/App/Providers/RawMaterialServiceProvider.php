<?php

namespace Modules\RawMaterial\App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * sdd.md §2: this is the 1:1 backend half of the Modules/RawMaterial ↔
 * frontend/src/modules/raw-material mapping.
 *
 * Routing is deliberately NOT wired here (no RouteServiceProvider in
 * $providers) — sdd.md §3 has backend/routes/api.php `require`
 * Modules/RawMaterial/routes/api.php directly instead, so keep route
 * definitions out of this provider.
 */
class RawMaterialServiceProvider extends ServiceProvider
{
    protected string $name = 'RawMaterial';

    protected string $nameLower = 'rawmaterial';
}
