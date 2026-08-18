<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\App\Models\AccountingCategory;

/**
 * @extends Factory<AccountingCategory>
 */
class AccountingCategoryFactory extends Factory
{
    protected $model = AccountingCategory::class;

    public function definition(): array
    {
        return [
            'kind' => 'income',
            'name' => fake()->unique()->word(),
            'description' => null,
            'is_active' => true,
        ];
    }

    public function income(): static
    {
        return $this->state(fn () => ['kind' => 'income']);
    }

    public function expense(): static
    {
        return $this->state(fn () => ['kind' => 'expense']);
    }
}
