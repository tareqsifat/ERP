<?php

namespace Modules\Production\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Location\App\Models\Location;
use Modules\Production\App\Http\Requests\QcRequest;
use Modules\Production\App\Http\Resources\PieceSerialResource;
use Modules\Production\App\Models\PieceSerial;
use Modules\Production\App\Services\QcService;

/**
 * PRD v2 §3.17/§3.18 — the traceability spine's read/QC endpoints.
 * `index` doubles as the serial-lookup search ("find every piece for
 * order X", "look up this exact serial") referenced by sdd.md's
 * TraceabilityTest; a dedicated Report-module endpoint (Phase 7) can
 * layer a friendlier UI on top of the same query later.
 */
class PieceSerialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);

        $pieces = PieceSerial::query()
            ->when($request->filled('serial'), fn ($q) => $q->where('serial', $request->string('serial')))
            ->when($request->filled('order_id'), fn ($q) => $q->where('order_id', $request->integer('order_id')))
            ->when($request->filled('bundle_id'), fn ($q) => $q->where('bundle_id', $request->integer('bundle_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('id')
            ->paginate($perPage);

        return $this->ok(PieceSerialResource::collection($pieces));
    }

    public function show(PieceSerial $pieceSerial): JsonResponse
    {
        return $this->ok(new PieceSerialResource($pieceSerial));
    }

    public function qc(QcRequest $request, PieceSerial $pieceSerial): JsonResponse
    {
        $data = $request->validated();

        if ($data['result'] === 'pass') {
            $location = Location::findOrFail($data['location_id']);
            $pieceSerial = QcService::pass($pieceSerial, $location, $request->user()->id);
        } else {
            $pieceSerial = QcService::reject($pieceSerial, $data['reason'], $request->user()->id);
        }

        return $this->ok(new PieceSerialResource($pieceSerial));
    }
}
