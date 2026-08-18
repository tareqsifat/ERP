<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\App\Models\BankAccount;

/**
 * @extends Factory<BankAccount>
 */
class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'account_holder_name' => fake()->company(),
            'bank_name' => fake()->company().' Bank',
            'account_number' => fake()->unique()->numerify('##########'),
            'branch_name' => fake()->city(),
            'routing_swift_no' => fake()->bothify('????##'),
            'is_active' => true,
        ];
    }
}
