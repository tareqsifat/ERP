<?php

namespace Modules\Accounting\App\Services;

use Modules\Accounting\App\Models\Voucher;

/**
 * PRD v1 §3.13/§4.12 — year-filterable Total Sale / Total Expense /
 * Total Profit / Total Loss. `total_sale` = every Credit Voucher for the
 * year (money in, regardless of purpose/category — a party payment and
 * a plain income entry both count as a "sale" at this coarse a level in
 * v1), `total_expense` = every Debit Voucher for the year. Net =
 * sale - expense; whichever of profit/loss is positive is shown, the
 * other is zero — never both nonzero at once.
 */
class LossProfitService
{
    public static function summarize(int $year): array
    {
        $totalSale = (string) (Voucher::query()->where('type', 'credit')->whereYear('date', $year)->sum('amount') ?: '0.00');
        $totalExpense = (string) (Voucher::query()->where('type', 'debit')->whereYear('date', $year)->sum('amount') ?: '0.00');

        $net = bcsub($totalSale, $totalExpense, 2);
        $profit = bccomp($net, '0', 2) > 0 ? $net : '0.00';
        $loss = bccomp($net, '0', 2) < 0 ? bcmul($net, '-1', 2) : '0.00';

        return [
            'year' => $year,
            'total_sale' => $totalSale,
            'total_expense' => $totalExpense,
            'total_profit' => $profit,
            'total_loss' => $loss,
        ];
    }
}
