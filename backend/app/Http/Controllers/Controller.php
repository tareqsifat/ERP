<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * sdd.md §3: consistent response envelope for every module controller —
 * {data, meta} for collections, {data} for single resources. Module
 * controllers (Modules/<Name>/app/Http/Controllers/*) extend this instead
 * of inventing their own response shape.
 */
abstract class Controller
{
    protected function ok(JsonResource|ResourceCollection|array $data, int $status = 200): JsonResponse
    {
        if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
            return $data->response()->setStatusCode($status);
        }

        return response()->json(['data' => $data], $status);
    }

    protected function created(JsonResource|array $data): JsonResponse
    {
        return $this->ok($data, 201);
    }

    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
