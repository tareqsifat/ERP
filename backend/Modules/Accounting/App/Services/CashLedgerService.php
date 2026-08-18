<?php

namespace Modules\Accounting\App\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Accounting\App\Models\CashTransaction;

/**
 * The ONLY code allowed to write to cash_transactions (sdd.md §5). One
 * pool for the whole factory — see the cash_transactions migration's
 * docblock.
 */
class CashLedgerService
{
    public static function increase(string $amount, int $createdBy, ?Model $reference = null, ?string $note = null): CashTransaction
    {
        return self::record('increase', $amount, $createdBy, $reference, $note);
    }

    public static function reduce(string $amount, int $createdBy, ?Model $reference = null, ?string $note = null): CashTransaction
    {
        return self::record('reduce', bcmul($amount, '-1', 2), $createdBy, $reference, $note);
    }

    public static function balance(): string
    {
        return (string) (CashTransaction::query()->sum('amount') ?: '0.00');
    }

    private static function record(string $type, string $signedAmount, int $createdBy, ?Model $reference, ?string $note): CashTransaction
    {
        $transaction = new CashTransaction([
            'type' => $type,
            'amount' => $signedAmount,
            'note' => $note,
            'occurred_on' => now()->toDateString(),
            'created_by' => $createdBy,
        ]);

        if ($reference) {
            $transaction->reference()->associate($reference);
        }

        $transaction->save();

        return $transaction;
    }
}
