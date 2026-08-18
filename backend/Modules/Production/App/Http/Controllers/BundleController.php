<?php

namespace Modules\Production\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Production\App\Http\Requests\AssignBundleToLineRequest;
use Modules\Production\App\Http\Resources\BundleResource;
use Modules\Production\App\Models\Bundle;
use Modules\Production\App\Models\Line;
use Modules\Production\App\Services\SewingService;

/**
 * PRD v2 §3.18 — Sewing line input/output. Bundles are never created
 * directly here (see App\Services\CuttingService); this controller only
 * moves a Bundle through the two sewing lifecycle actions.
 */
class BundleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);

        $bundles = Bundle::query()
            ->when($request->filled('cut_ticket_id'), fn ($q) => $q->where('cut_ticket_id', $request->integer('cut_ticket_id')))
            ->when($request->filled('line_id'), fn ($q) => $q->where('line_id', $request->integer('line_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('id')
            ->paginate($perPage);

        return $this->ok(BundleResource::collection($bundles));
    }

    public function show(Bundle $bundle): JsonResponse
    {
        $bundle->load('pieceSerials');

        return $this->ok(new BundleResource($bundle));
    }

    public function assignToLine(AssignBundleToLineRequest $request, Bundle $bundle): JsonResponse
    {
        $line = Line::findOrFail($request->validated('line_id'));
        $bundle = SewingService::assignToLine($bundle, $line);

        return $this->ok(new BundleResource($bundle));
    }

    public function logOutput(Bundle $bundle): JsonResponse
    {
        $bundle = SewingService::logOutput($bundle);

        return $this->ok(new BundleResource($bundle));
    }
}
