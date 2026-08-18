<?php

namespace Modules\Accounting\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Accounting\App\Http\Requests\CashTransactionRequest;
use Modules\Accounting\App\Http\Resources\CashTransactionResource;
use Modules\Accounting\App\Models\CashTransaction;
use Modules\Accounting\App\Services\CashLedgerService;

// PRD v1 §3.9/§4.8 — Cash in Hand.
class CashController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);

        $transactions = CashTransaction::query()
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'data' => CashTransactionResource::collection($transactions)->resolve(),
            'meta' => [
                'balance' => CashLedgerService::balance(),
                'total' => $transactions->total(),
                'current_page' => $transactions->currentPage(),
                'per_page' => $transactions->perPage(),
            ],
        ]);
    }

    public function increase(CashTransactionRequest $request): JsonResponse
    {
        $transaction = CashLedgerService::increase((string) $request->validated('amount'), $request->user()->id, null, $request->validated('note'));

        return $this->created(new CashTransactionResource($transaction));
    }

    public function reduce(CashTransactionRequest $request): JsonResponse
    {
        $transaction = CashLedgerService::reduce((string) $request->validated('amount'), $request->user()->id, null, $request->validated('note'));

        return $this->created(new CashTransactionResource($transaction));
    }
}
