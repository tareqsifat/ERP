<?php

namespace Modules\Subcontract\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Production\App\Http\Resources\CutTicketResource;
use Modules\Production\App\Http\Resources\PieceSerialResource;
use Modules\Subcontract\App\Http\Requests\IssueSubcontractPiecesRequest;
use Modules\Subcontract\App\Http\Requests\IssueSubcontractRawMaterialRequest;
use Modules\Subcontract\App\Http\Requests\ReturnSubcontractPiecesRequest;
use Modules\Subcontract\App\Http\Requests\StoreSubcontractOrderRequest;
use Modules\Subcontract\App\Http\Requests\StoreSubcontractPaymentRequest;
use Modules\Subcontract\App\Http\Resources\SubcontractLedgerEntryResource;
use Modules\Subcontract\App\Http\Resources\SubcontractOrderResource;
use Modules\Subcontract\App\Models\SubcontractOrder;
use Modules\Subcontract\App\Services\SubcontractInwardService;
use Modules\Subcontract\App\Services\SubcontractLedgerService;
use Modules\Subcontract\App\Services\SubcontractNumberGenerator;
use Modules\Subcontract\App\Services\SubcontractOutwardService;

/**
 * PRD v2 §3.23/§3.24/§4.8/§4.9 — Outward and Inward Subcontract Orders
 * share this one controller since they share the same shape (`direction`
 * is the only structural difference) — see App\Models\SubcontractOrder
 * and the module README for the full design rationale.
 */
class SubcontractOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);

        $orders = SubcontractOrder::query()
            ->when($request->filled('direction'), fn ($q) => $q->where('direction', $request->string('direction')))
            ->when($request->filled('party_id'), fn ($q) => $q->where('party_id', $request->integer('party_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('id')
            ->paginate($perPage);

        return $this->ok(SubcontractOrderResource::collection($orders));
    }

    public function show(SubcontractOrder $subcontractOrder): JsonResponse
    {
        $subcontractOrder->load('pieces.pieceSerial', 'ledgerEntries');

        return $this->ok(new SubcontractOrderResource($subcontractOrder));
    }

    public function store(StoreSubcontractOrderRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Sequence must be resolved and assigned BEFORE the first save() —
        // `year`/`sequence_no` are NOT NULL columns with no default (same
        // lesson as Shipment/RawMaterialPurchaseOrder/StockTransfer's
        // identical pattern — see failed_doc.md Pass 2).
        $year = (int) now()->year;
        $sequence = SubcontractNumberGenerator::nextFor($year);

        $order = new SubcontractOrder($data);
        $order->created_by = $request->user()->id;
        $order->year = $year;
        $order->sequence_no = $sequence;
        $order->subcontract_no = SubcontractNumberGenerator::format($year, $sequence);
        $order->save();

        return $this->created(new SubcontractOrderResource($order));
    }

    public function issuePieces(IssueSubcontractPiecesRequest $request, SubcontractOrder $subcontractOrder): JsonResponse
    {
        $pieces = SubcontractOutwardService::issuePieces(
            $subcontractOrder,
            $request->validated('piece_serial_ids'),
            $request->user()->id,
        );

        return $this->ok(PieceSerialResource::collection(collect($pieces)));
    }

    public function issueRawMaterial(IssueSubcontractRawMaterialRequest $request, SubcontractOrder $subcontractOrder): JsonResponse
    {
        $cutTicket = SubcontractOutwardService::issueRawMaterial(
            $subcontractOrder,
            $request->validated('cut_date'),
            (int) $request->validated('cutting_master_id'),
            (int) $request->validated('bundle_size'),
            (int) $request->validated('quantity'),
            $request->user()->id,
        );

        return $this->created(new CutTicketResource($cutTicket));
    }

    public function returnPieces(ReturnSubcontractPiecesRequest $request, SubcontractOrder $subcontractOrder): JsonResponse
    {
        $order = SubcontractOutwardService::returnPieces(
            $subcontractOrder,
            $request->validated('returned_piece_serial_ids') ?? [],
            $request->validated('written_off_piece_serial_ids') ?? [],
            $request->user()->id,
        );

        return $this->ok(new SubcontractOrderResource($order));
    }

    public function dispatchBack(Request $request, SubcontractOrder $subcontractOrder): JsonResponse
    {
        $order = SubcontractInwardService::dispatchBack($subcontractOrder, $request->user()->id);

        return $this->ok(new SubcontractOrderResource($order));
    }

    public function ledger(SubcontractOrder $subcontractOrder): JsonResponse
    {
        $entries = $subcontractOrder->ledgerEntries()->orderByDesc('id')->get();

        return $this->ok(SubcontractLedgerEntryResource::collection($entries));
    }

    public function payment(StoreSubcontractPaymentRequest $request, SubcontractOrder $subcontractOrder): JsonResponse
    {
        $entry = SubcontractLedgerService::post(
            $subcontractOrder,
            'payment',
            (string) $request->validated('amount'),
            $request->user()->id,
            $request->validated('remarks'),
        );

        return $this->created(new SubcontractLedgerEntryResource($entry));
    }
}
