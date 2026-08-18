<?php

namespace Modules\Accounting\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Accounting\App\Http\Resources\CashTransactionResource;
use Modules\Accounting\App\Models\CashTransaction;

/**
 * PRD v1 §3.9/§4.8 — Daily Cashbook: date-ranged cash register with a
 * running summary panel (Previous Balance, Credit, Sub Total, Total
 * Expenses, Cash In Hand).
 */
class CashbookController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $from = $request->filled('from') ? $request->date('from')->toDateString() : now()->startOfMonth()->toDateString();
        $to = $request->filled('to') ? $request->date('to')->toDateString() : now()->toDateString();

        $previousBalance = (string) (CashTransaction::query()->where('occurred_on', '<', $from)->sum('amount') ?: '0.00');

        $entries = CashTransaction::query()
            ->whereBetween('occurred_on', [$from, $to])
            ->orderBy('occurred_on')
            ->orderBy('id')
            ->get();

        $credit = (string) ($entries->where('type', 'increase')->sum('amount') ?: '0.00');
        $expense = (string) (bcmul((string) $entries->where('type', 'reduce')->sum('amount'), '-1', 2));
        $subTotal = bcadd($previousBalance, $credit, 2);
        $cashInHand = bcsub($subTotal, $expense, 2);

        return response()->json([
            'data' => CashTransactionResource::collection($entries)->resolve(),
            'meta' => [
                'from' => $from,
                'to' => $to,
                'previous_balance' => $previousBalance,
                'credit' => $credit,
                'sub_total' => $subTotal,
                'total_expenses' => $expense,
                'cash_in_hand' => $cashInHand,
            ],
        ]);
    }
}
