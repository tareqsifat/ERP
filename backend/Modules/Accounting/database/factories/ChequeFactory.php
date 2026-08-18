<?php

namespace Modules\Accounting\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\App\Models\BankAccount;
use Modules\Accounting\App\Models\Cheque;

/**
 * @extends Factory<Cheque>
 */
class ChequeFactory extends Factory
{
    protected $model = Cheque::class;

    public function definition(): array
    {
        return [
            'party_id' => null,
            'bank_account_id' => BankAccount::factory(),
            'cheque_no' => fake()->unique()->numerify('CHQ-######'),
            'amount' => 5000,
            'issue_date' => now()->toDateString(),
            'type' => 'expense',
            'status' => 'unused',
            'created_by' => User::factory(),
        ];
    }
}
