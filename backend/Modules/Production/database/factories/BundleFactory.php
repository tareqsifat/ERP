<?php

namespace Modules\Production\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Production\App\Models\Bundle;
use Modules\Production\App\Models\CutTicket;

/**
 * @extends Factory<Bundle>
 *
 * For tests that need a Bundle in isolation (e.g. exercising
 * SewingService/QcService directly) without paying for a full
 * CuttingService::finalize() run. Production code never uses this
 * factory — Bundles are only ever created by CuttingService.
 */
class BundleFactory extends Factory
{
    protected $model = Bundle::class;

    public function definition(): array
    {
        return [
            'cut_ticket_id' => CutTicket::factory(),
            'bundle_no' => str_pad((string) fake()->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'quantity' => 20,
            'status' => 'cut',
        ];
    }
}
