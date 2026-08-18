<?php

namespace Modules\Location\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Location\App\Models\Location;

/**
 * todo.md Phase 4 / PRD v2 §3.21: "1 Factory, 1 Main Store, 3 Showrooms
 * (named/configurable)". Names here are generic placeholders — rename via
 * the Locations UI once the client confirms real showroom names/areas.
 */
class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            ['name' => 'Factory', 'type' => 'factory'],
            ['name' => 'Main Store', 'type' => 'store'],
            ['name' => 'Showroom 1', 'type' => 'showroom'],
            ['name' => 'Showroom 2', 'type' => 'showroom'],
            ['name' => 'Showroom 3', 'type' => 'showroom'],
        ];

        foreach ($locations as $location) {
            Location::firstOrCreate(['name' => $location['name']], $location);
        }
    }
}
