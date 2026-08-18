<?php

namespace Modules\Production\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Production\App\Http\Requests\StoreLineRequest;
use Modules\Production\App\Http\Requests\UpdateLineRequest;
use Modules\Production\App\Http\Resources\LineResource;
use Modules\Production\App\Models\Line;

/**
 * PRD v2 §3.22 / §4.7 — Machine/Line register (lives in Modules/Production
 * per sdd.md §2's repo layout, gated by the `machine.*` permissions).
 */
class LineController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 50), 100);

        $lines = Line::query()
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->paginate($perPage);

        return $this->ok(LineResource::collection($lines));
    }

    public function show(Line $line): JsonResponse
    {
        return $this->ok(new LineResource($line));
    }

    public function store(StoreLineRequest $request): JsonResponse
    {
        $line = Line::create($request->validated());

        return $this->created(new LineResource($line));
    }

    public function update(UpdateLineRequest $request, Line $line): JsonResponse
    {
        $line->fill($request->validated());
        $line->save();

        return $this->ok(new LineResource($line));
    }

    public function destroy(Line $line): JsonResponse
    {
        $line->delete();

        return $this->noContent();
    }
}
