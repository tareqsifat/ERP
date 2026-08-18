<?php

namespace Modules\Accounting\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Accounting\App\Models\Voucher;

/**
 * PRD v1 §3.9/§4.8 — Monthly Transaction: a year/month-filterable daily
 * rollup of vouchers (Date, Total Transaction, Total Amount, Type).
 * Grouped by date+type since a day can carry both credit and debit
 * activity, shown as separate rows (matches the PRD's "Type" column
 * being singular per row, not a mixed total).
 */
class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $year = (int) $request->integer('year', now()->year);
        $month = $request->filled('month') ? (int) $request->integer('month') : null;

        $rows = Voucher::query()
            ->selectRaw('date, type, COUNT(*) as total_transactions, SUM(amount) as total_amount')
            ->whereYear('date', $year)
            ->when($month, fn ($q) => $q->whereMonth('date', $month))
            ->groupBy('date', 'type')
            ->orderByDesc('date')
            ->get();

        return $this->ok($rows->toArray());
    }
}
