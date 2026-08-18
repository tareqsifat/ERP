<?php

namespace Modules\Report\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Report\App\Services\ReportService;
use Modules\Report\App\Services\TraceabilityService;

/**
 * PRD v1 §3.14/§4.13 — Reports section. All seven report types are
 * gated by the single `report.view` permission (see Report/README.md);
 * two of the seven — Daily Cashbook and Piece Traceability — delegate
 * to logic already built in Modules/Accounting and Modules/Production
 * respectively rather than duplicating it (ReportService/
 * TraceabilityService docblocks).
 */
class ReportController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->ok([
            'types' => [
                ['key' => 'sales-orders', 'label' => 'Sales / Order Report'],
                ['key' => 'production', 'label' => 'Production Report'],
                ['key' => 'stock', 'label' => 'Stock Report'],
                ['key' => 'subcontract', 'label' => 'Subcontract Report'],
                ['key' => 'party-ledger', 'label' => 'Party Ledger Report'],
                ['key' => 'cashbook', 'label' => 'Daily Cashbook'],
                ['key' => 'traceability', 'label' => 'Piece Traceability Lookup'],
            ],
        ]);
    }

    public function salesOrders(Request $request): JsonResponse
    {
        return $this->ok(ReportService::salesOrders($request->date('from')?->toDateString(), $request->date('to')?->toDateString()));
    }

    public function production(Request $request): JsonResponse
    {
        return $this->ok(ReportService::production($request->date('from')?->toDateString(), $request->date('to')?->toDateString()));
    }

    public function stock(): JsonResponse
    {
        return $this->ok(ReportService::stock());
    }

    public function subcontract(Request $request): JsonResponse
    {
        return $this->ok(ReportService::subcontract($request->date('from')?->toDateString(), $request->date('to')?->toDateString()));
    }

    public function partyLedger(Request $request): JsonResponse
    {
        return $this->ok(ReportService::partyLedger($request->string('type')->value() ?: null));
    }

    public function cashbook(Request $request): JsonResponse
    {
        return response()->json(ReportService::cashbook($request));
    }

    public function traceability(string $serial): JsonResponse
    {
        $trace = TraceabilityService::traceBySerial($serial);

        if (! $trace) {
            return response()->json(['message' => 'No piece found for that serial.'], 404);
        }

        return $this->ok($trace);
    }
}
