<?php

namespace Modules\Accounting\App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Accounting\App\Models\BankAccount;
use Modules\Accounting\App\Models\Voucher;

/**
 * PRD v1 §3.9/§4.8/§6.6 — Credit/Debit Voucher creation. This is the one
 * place a Voucher is ever created (never a plain
 * `Voucher::create($request->validated())` in a controller), because
 * creating one has real side effects: it posts to exactly one of the
 * cash/bank ledgers per `payment_type` (cheque posts nothing yet — see
 * App\Services\ChequeService::markPassed()), and — when tied to a party
 * — is what App\Services\PartyFinancialsService reads back out as
 * "paid"/"advance".
 */
class VoucherService
{
    public static function record(array $data, int $createdBy): Voucher
    {
        return DB::transaction(function () use ($data, $createdBy) {
            // Sequence must be resolved and assigned BEFORE the first
            // save() — `year`/`sequence_no` are NOT NULL columns with no
            // default (same lesson as every other numbered document in
            // this system — see failed_doc.md Pass 2).
            $year = (int) now()->year;
            $sequence = VoucherNumberGenerator::nextFor($data['type'], $year);

            $voucher = new Voucher($data);
            $voucher->created_by = $createdBy;
            $voucher->year = $year;
            $voucher->sequence_no = $sequence;
            $voucher->voucher_no = VoucherNumberGenerator::format($data['type'], $year, $sequence);
            $voucher->save();

            match ($voucher->payment_type) {
                'cash' => $voucher->type === 'credit'
                    ? CashLedgerService::increase((string) $voucher->amount, $createdBy, $voucher, $voucher->remarks)
                    : CashLedgerService::reduce((string) $voucher->amount, $createdBy, $voucher, $voucher->remarks),
                'bank' => self::postBank($voucher, $createdBy),
                'cheque' => null, // deferred to ChequeService::markPassed()
                default => throw ValidationException::withMessages(['payment_type' => 'Unknown payment type.']),
            };

            return $voucher;
        });
    }

    private static function postBank(Voucher $voucher, int $createdBy): void
    {
        if (! $voucher->bank_account_id) {
            throw ValidationException::withMessages(['bank_account_id' => 'A bank account is required for a bank payment.']);
        }

        $account = BankAccount::findOrFail($voucher->bank_account_id);

        if ($voucher->type === 'credit') {
            BankLedgerService::deposit($account, (string) $voucher->amount, $createdBy, $voucher, $voucher->remarks);
        } else {
            BankLedgerService::withdraw($account, (string) $voucher->amount, $createdBy, $voucher, $voucher->remarks);
        }
    }
}
