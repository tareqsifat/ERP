<?php

namespace Modules\Sampling\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Sampling\App\Http\Requests\StoreSampleRequest;
use Modules\Sampling\App\Http\Requests\UpdateSampleRequest;
use Modules\Sampling\App\Http\Resources\SampleResource;
use Modules\Sampling\App\Models\Sample;

/**
 * PRD v1 §3.4 (Sampling).
 */
class SampleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);

        $samples = Sample::query()
            ->when($request->filled('order_id'), fn ($q) => $q->where('order_id', $request->integer('order_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('id')
            ->paginate($perPage);

        return $this->ok(SampleResource::collection($samples));
    }

    public function show(Sample $sample): JsonResponse
    {
        return $this->ok(new SampleResource($sample));
    }

    public function store(StoreSampleRequest $request): JsonResponse
    {
        $sample = Sample::create($request->validated());

        return $this->created(new SampleResource($sample));
    }

    public function update(UpdateSampleRequest $request, Sample $sample): JsonResponse
    {
        $sample->fill($request->validated());
        $sample->save();

        return $this->ok(new SampleResource($sample));
    }

    public function destroy(Sample $sample): JsonResponse
    {
        $sample->delete();

        return $this->noContent();
    }
}
