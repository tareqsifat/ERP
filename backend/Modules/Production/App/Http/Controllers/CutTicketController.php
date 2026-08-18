<?php

namespace Modules\Production\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Production\App\Http\Requests\StoreCutTicketRequest;
use Modules\Production\App\Http\Requests\UpdateCutTicketRequest;
use Modules\Production\App\Http\Resources\CutTicketResource;
use Modules\Production\App\Models\CutTicket;
use Modules\Production\App\Services\CuttingService;

/**
 * PRD v2 §3.17 / §4.4 — Cut Ticket is the entry point of the whole
 * traceability spine. A ticket is `draft` (freely editable, no stock/
 * serial impact) until POST .../finalize, which is the one irreversible
 * action here — see App\Services\CuttingService.
 */
class CutTicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);

        $tickets = CutTicket::query()
            ->when($request->filled('order_id'), fn ($q) => $q->where('order_id', $request->integer('order_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('id')
            ->paginate($perPage);

        return $this->ok(CutTicketResource::collection($tickets));
    }

    public function show(CutTicket $cutTicket): JsonResponse
    {
        $cutTicket->load('bundles.pieceSerials');

        return $this->ok(new CutTicketResource($cutTicket));
    }

    public function store(StoreCutTicketRequest $request): JsonResponse
    {
        $cutTicket = CutTicket::create($request->validated());

        return $this->created(new CutTicketResource($cutTicket));
    }

    public function update(UpdateCutTicketRequest $request, CutTicket $cutTicket): JsonResponse
    {
        $this->guardDraft($cutTicket);

        $cutTicket->fill($request->validated());
        $cutTicket->save();

        return $this->ok(new CutTicketResource($cutTicket));
    }

    public function destroy(CutTicket $cutTicket): JsonResponse
    {
        $this->guardDraft($cutTicket);

        $cutTicket->delete();

        return $this->noContent();
    }

    public function finalize(Request $request, CutTicket $cutTicket): JsonResponse
    {
        $cutTicket = CuttingService::finalize($cutTicket, $request->user()->id);
        $cutTicket->load('bundles.pieceSerials');

        return $this->ok(new CutTicketResource($cutTicket));
    }

    private function guardDraft(CutTicket $cutTicket): void
    {
        if ($cutTicket->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'A finalized cut ticket can no longer be edited or deleted.',
            ]);
        }
    }
}
