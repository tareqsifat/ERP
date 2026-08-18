<?php

namespace Modules\Accounting\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Accounting\App\Http\Requests\StorePartyBillRequest;
use Modules\Accounting\App\Http\Resources\PartyBillResource;
use Modules\Accounting\App\Http\Resources\VoucherResource;
use Modules\Accounting\App\Models\PartyBill;
use Modules\Accounting\App\Services\PartyFinancialsService;
use Modules\Party\App\Http\Resources\PartyResource;
use Modules\Party\App\Models\Party;

/**
 * PRD v1 §3.10/§3.12/§4.9/§4.11 — Party Ledger and Party Due List are the
 * same underlying data (per-party total_bill/paid/advance/due/balance,
 * see App\Services\PartyFinancialsService), just different page framing
 * in the PRD (Buyer/Supplier tabs vs. a dues dashboard) — one endpoint
 * serves both; the frontend renders it two ways. The PRD's other two
 * Party Due List tabs ("Credit Voucher"/"Debit Voucher") are just the
 * existing voucher list filtered by type — no separate backend needed
 * (see Accounting/README.md "Known simplifications").
 */
class PartyLedgerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);

        $parties = Party::query()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->orderBy('name')
            ->paginate($perPage);

        $rows = collect($parties->items())->map(fn (Party $party) => [
            'party' => (new PartyResource($party))->resolve(),
            'financials' => PartyFinancialsService::summarize($party),
        ]);

        return response()->json([
            'data' => $rows,
            'meta' => [
                'total' => $parties->total(),
                'current_page' => $parties->currentPage(),
                'per_page' => $parties->perPage(),
            ],
        ]);
    }

    public function show(Party $party): JsonResponse
    {
        return response()->json([
            'data' => [
                'party' => (new PartyResource($party))->resolve(),
                'financials' => PartyFinancialsService::summarize($party),
                'bills' => PartyBillResource::collection($party->bills()->orderByDesc('id')->get()),
                'vouchers' => VoucherResource::collection($party->vouchers()->orderByDesc('id')->get()),
            ],
        ]);
    }

    public function storeBill(StorePartyBillRequest $request, Party $party): JsonResponse
    {
        $data = $request->validated();
        $data['party_id'] = $party->id;
        $data['created_by'] = $request->user()->id;

        $bill = PartyBill::create($data);

        return $this->created(new PartyBillResource($bill));
    }
}
