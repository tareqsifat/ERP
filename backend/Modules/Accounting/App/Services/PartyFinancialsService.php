<?php

namespace Modules\Accounting\App\Services;

use Modules\Accounting\App\Models\PartyBill;
use Modules\Accounting\App\Models\Voucher;
use Modules\Party\App\Models\Party;

/**
 * PRD v1 §3.10/§3.12 — the "total bill/advance/paid/due/balance" figures
 * every Party List/Party Ledger/Party Due List page shows. Closes the
 * gap Modules/Party/README.md and Party.php's docblock explicitly
 * deferred to "once Modules/Accounting exists (Phase 6)".
 *
 * Definitions (a deliberate v1 interpretation — PRD's prose doesn't
 * pin these down precisely, see Accounting/README.md "Known
 * simplifications"):
 *   - total_bill = SUM(PartyBill.amount) for this party
 *   - paid       = SUM(Voucher.amount) where party_id = this party AND purpose = 'payment'
 *   - advance    = SUM(Voucher.amount) where party_id = this party AND purpose = 'advance'
 *   - due        = max(total_bill - paid, 0)
 *   - balance    = advance - due  (positive = they've prepaid beyond what's due;
 *                                   negative = still owed beyond any advance held)
 * `paid`/`advance` read Voucher rows regardless of `type` (credit or
 * debit) — a Buyer's payments to us are credit vouchers, a Supplier/
 * Subcontractor's payments from us are debit vouchers, but "money
 * changed hands with this party" is the same concept either way.
 */
class PartyFinancialsService
{
    public static function summarize(Party $party): array
    {
        $totalBill = (string) (PartyBill::query()->where('party_id', $party->id)->sum('amount') ?: '0.00');
        $paid = (string) (Voucher::query()->where('party_id', $party->id)->where('purpose', 'payment')->sum('amount') ?: '0.00');
        $advance = (string) (Voucher::query()->where('party_id', $party->id)->where('purpose', 'advance')->sum('amount') ?: '0.00');

        $due = bcsub($totalBill, $paid, 2);
        if (bccomp($due, '0', 2) < 0) {
            $due = '0.00';
        }

        $balance = bcsub($advance, $due, 2);

        return [
            'total_bill' => $totalBill,
            'paid' => $paid,
            'advance' => $advance,
            'due' => $due,
            'balance' => $balance,
        ];
    }
}
