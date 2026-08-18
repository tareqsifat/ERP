<?php

namespace Modules\Party\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Party\App\Models\Party;

/**
 * @extends Factory<Party>
 */
class PartyFactory extends Factory
{
    protected $model = Party::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => fake()->randomElement(['buyer', 'supplier', 'subcontractor']),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'country' => fake()->country(),
            'opening_balance_type' => fake()->randomElement(['debit', 'credit']),
            'opening_balance' => fake()->randomFloat(2, 0, 50000),
            'remarks' => null,
            'image_path' => null,
            'is_active' => true,
        ];
    }

    public function buyer(): static
    {
        return $this->state(fn () => ['type' => 'buyer']);
    }

    public function supplier(): static
    {
        return $this->state(fn () => ['type' => 'supplier']);
    }

    public function subcontractor(): static
    {
        return $this->state(fn () => ['type' => 'subcontractor']);
    }
}
