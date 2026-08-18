<?php

namespace Modules\Accounting\App\Services;

use Modules\Accounting\App\Models\Voucher;

/**
 * `CR-YYYY-NNNN` (credit) / `DR-YYYY-NNNN` (debit) — separate
 * per-type-per-year sequences (see the vouchers migration's
 * unique(['type','year','sequence_no'])), same race-safe
 * lockForUpdate() pattern as every other numbered document.
 */
class VoucherNumberGenerator
{
    public static function nextFor(string $type, int $year): int
    {
        $maxSequence = Voucher::withTrashed()
            ->where('type', $type)
            ->where('year', $year)
            ->lockForUpdate()
            ->max('sequence_no');

        return ($maxSequence ?? 0) + 1;
    }

    public static function format(string $type, int $year, int $sequence): string
    {
        $prefix = $type === 'credit' ? 'CR' : 'DR';

        return sprintf('%s-%d-%04d', $prefix, $year, $sequence);
    }
}
