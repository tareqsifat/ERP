<?php

namespace Modules\Accounting\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\App\Models\Voucher;
use Modules\Accounting\App\Services\VoucherNumberGenerator;

/**
 * @extends Factory<Voucher>
 *
 * Test-only shortcut for exercising read endpoints without going through
 * App\Services\VoucherService::record() (which also posts a ledger side
 * effect) — production code never creates a Voucher via this factory.
 * year/sequence_no/voucher_no computed here rather than in an
 * afterCreating() callback — same NOT-NULL-before-first-save() lesson as
 * every other numbered-document factory in this system.
 */
class VoucherFactory extends Factory
{
    protected $model = Voucher::class;

    public function definition(): array
    {
        $type = 'credit';
        $year = (int) now()->year;
        $sequence = VoucherNumberGenerator::nextFor($type, $year);

        return [
            'type' => $type,
            'purpose' => 'general',
            'amount' => 1000,
            'payment_type' => 'cash',
            'date' => now()->toDateString(),
            'created_by' => User::factory(),
            'year' => $year,
            'sequence_no' => $sequence,
            'voucher_no' => VoucherNumberGenerator::format($type, $year, $sequence),
        ];
    }

    public function debit(): static
    {
        return $this->state(function () {
            $year = (int) now()->year;
            $sequence = VoucherNumberGenerator::nextFor('debit', $year);

            return [
                'type' => 'debit',
                'year' => $year,
                'sequence_no' => $sequence,
                'voucher_no' => VoucherNumberGenerator::format('debit', $year, $sequence),
            ];
        });
    }
}
