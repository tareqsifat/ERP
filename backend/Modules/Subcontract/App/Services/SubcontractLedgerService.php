<?php

namespace Modules\Subcontract\App\Services;

use Modules\Subcontract\App\Models\SubcontractLedgerEntry;
use Modules\Subcontract\App\Models\SubcontractOrder;

/**
 * The ONLY code allowed to write to subcontract_ledger_entries (sdd.md
 * §5: ledger is the source of truth, append-only). issue_value/
 * return_value/shortage_deduction/job_work_income are posted exclusively
 * by SubcontractOutwardService/SubcontractInwardService as a side effect
 * of a piece movement; `payment` is the only type a controller posts
 * directly (see SubcontractLedgerController::store()), since a payment
 * isn't tied to a piece event.
 *
 * Known gap (documented in README): these entries are not yet posted
 * into Modules/Accounting — that wiring is deferred to Phase 6, same
 * caveat as Party's `total_bill/advance/paid/due` figures.
 */
class SubcontractLedgerService
{
    public static function post(SubcontractOrder $order, string $type, string $amount, int $createdBy, ?string $remarks = null): SubcontractLedgerEntry
    {
        $entry = new SubcontractLedgerEntry([
            'subcontract_order_id' => $order->id,
            'party_id' => $order->party_id,
            'type' => $type,
            'amount' => $amount,
            'occurred_on' => now()->toDateString(),
            'remarks' => $remarks,
            'created_by' => $createdBy,
        ]);
        $entry->save();

        return $entry;
    }
}
