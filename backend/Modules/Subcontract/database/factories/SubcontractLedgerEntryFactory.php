<?php

namespace Modules\Subcontract\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Subcontract\App\Models\SubcontractLedgerEntry;
use Modules\Subcontract\App\Models\SubcontractOrder;

/**
 * @extends Factory<SubcontractLedgerEntry>
 */
class SubcontractLedgerEntryFactory extends Factory
{
    protected $model = SubcontractLedgerEntry::class;

    public function definition(): array
    {
        $order = SubcontractOrder::factory()->create();

        return [
            'subcontract_order_id' => $order->id,
            'party_id' => $order->party_id,
            'type' => 'issue_value',
            'amount' => 1000,
            'occurred_on' => now()->toDateString(),
            'created_by' => User::factory(),
        ];
    }
}
