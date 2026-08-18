<?php

namespace Modules\Setting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Setting\App\Models\Setting;

class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'key' => 'test.'.$this->faker->unique()->word(),
            'value' => ['value' => $this->faker->word()],
            'group' => $this->faker->randomElement(['currency', 'notification', 'system', 'company']),
        ];
    }
}
