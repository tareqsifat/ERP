<?php

namespace Modules\Accounting\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\App\Models\PartyBill;
use Modules\Party\App\Models\Party;

/**
 * @extends Factory<PartyBill>
 */
class PartyBillFactory extends Factory
{
    protected $model = PartyBill::class;

    public function definition(): array
    {
        return [
            'party_id' => Party::factory(),
            'amount' => 10000,
            'bill_date' => now()->toDateString(),
            'description' => 'Test bill',
            'reference' => null,
            'created_by' => User::factory(),
        ];
    }
}
