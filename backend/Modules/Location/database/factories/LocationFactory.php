<?php

namespace Modules\Location\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Location\App\Models\Location;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city().' '.fake()->randomElement(['Factory', 'Store', 'Showroom']),
            'type' => fake()->randomElement(['factory', 'store', 'showroom']),
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }

    // Named ofType*() rather than factory()/store() to avoid any confusion
    // with Eloquent's own Factory vocabulary.
    public function ofTypeFactory(): static
    {
        return $this->state(fn () => ['type' => 'factory']);
    }

    public function ofTypeStore(): static
    {
        return $this->state(fn () => ['type' => 'store']);
    }

    public function ofTypeShowroom(): static
    {
        return $this->state(fn () => ['type' => 'showroom']);
    }
}
