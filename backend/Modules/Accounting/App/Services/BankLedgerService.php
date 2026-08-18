<?php

namespace Modules\Accounting\App\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Accounting\App\Models\BankAccount;
use Modules\Accounting\App\Models\BankTransaction;

/**
 * The ONLY code allowed to write to bank_transactions (sdd.md §5 — same
 * contract as RawMaterialStockService). A bank account's balance is
 * always SUM(signed amount) over this table, never a stored column.
 */
class BankLedgerService
{
    public static function deposit(BankAccount $account, string $amount, int $createdBy, ?Model $reference = null, ?string $remarks = null): BankTransaction
    {
        return self::record($account, 'deposit', $amount, $createdBy, $reference, $remarks);
    }

    public static function withdraw(BankAccount $account, string $amount, int $createdBy, ?Model $reference = null, ?string $remarks = null): BankTransaction
    {
        return self::record($account, 'withdraw', bcmul($amount, '-1', 2), $createdBy, $reference, $remarks);
    }

    public static function balanceOf(BankAccount $account): string
    {
        return (string) ($account->transactions()->sum('amount') ?: '0.00');
    }

    private static function record(BankAccount $account, string $type, string $signedAmount, int $createdBy, ?Model $reference, ?string $remarks): BankTransaction
    {
        $transaction = new BankTransaction([
            'bank_account_id' => $account->id,
            'type' => $type,
            'amount' => $signedAmount,
            'occurred_on' => now()->toDateString(),
            'remarks' => $remarks,
            'created_by' => $createdBy,
        ]);

        if ($reference) {
            $transaction->reference()->associate($reference);
        }

        $transaction->save();

        return $transaction;
    }
}
