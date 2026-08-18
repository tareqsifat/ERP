<?php

namespace Modules\Accounting\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Accounting\App\Http\Requests\StoreChequeRequest;
use Modules\Accounting\App\Http\Resources\ChequeResource;
use Modules\Accounting\App\Models\Cheque;
use Modules\Accounting\App\Services\ChequeService;

// PRD v1 §3.9/§4.8 — Cheques (Passed/Unused tabs).
class ChequeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);

        $cheques = Cheque::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->orderByDesc('id')
            ->paginate($perPage);

        return $this->ok(ChequeResource::collection($cheques));
    }

    public function store(StoreChequeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $cheque = Cheque::create($data);

        return $this->created(new ChequeResource($cheque));
    }

    public function markPassed(Request $request, Cheque $cheque): JsonResponse
    {
        $cheque = ChequeService::markPassed($cheque, $request->user()->id);

        return $this->ok(new ChequeResource($cheque));
    }
}
