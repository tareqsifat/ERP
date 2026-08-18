<?php

namespace Modules\Accounting\App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Accounting\App\Models\BankAccount;
use Modules\Accounting\App\Models\Cheque;

/**
 * PRD v1 §3.9/§4.8 — Cheques (Passed/Unused). `markPassed()` is the one
 * place a Cheque affects the bank ledger — see the cheques migration's
 * docblock for why clearing is modeled as a separate later event rather
 * than immediate at issue/receipt time.
 */
class ChequeService
{
    public static function markPassed(Cheque $cheque, int $passedBy): Cheque
    {
        if ($cheque->status === 'passed') {
            // Idempotency guard — same rationale as every other
            // one-way-transition action in this system: passing twice
            // would double-post a bank transaction.
            throw ValidationException::withMessages([
                'status' => "Cheque {$cheque->cheque_no} has already been passed.",
            ]);
        }

        return DB::transaction(function () use ($cheque, $passedBy) {
            $account = BankAccount::findOrFail($cheque->bank_account_id);

            if ($cheque->type === 'income') {
                BankLedgerService::deposit($account, (string) $cheque->amount, $passedBy, $cheque, "Cheque {$cheque->cheque_no} passed");
            } else {
                BankLedgerService::withdraw($account, (string) $cheque->amount, $passedBy, $cheque, "Cheque {$cheque->cheque_no} passed");
            }

            $cheque->status = 'passed';
            $cheque->passed_at = now();
            $cheque->save();

            return $cheque;
        });
    }
}
