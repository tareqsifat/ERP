<?php

namespace Modules\Accounting\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Accounting\App\Http\Requests\StoreVoucherRequest;
use Modules\Accounting\App\Http\Resources\VoucherResource;
use Modules\Accounting\App\Models\Voucher;
use Modules\Accounting\App\Services\VoucherService;

// PRD v1 §3.9/§4.8/§6.6 — Credit/Debit Vouchers.
class VoucherController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);

        $vouchers = Voucher::query()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('party_id'), fn ($q) => $q->where('party_id', $request->integer('party_id')))
            ->when($request->filled('purpose'), fn ($q) => $q->where('purpose', $request->string('purpose')))
            ->orderByDesc('id')
            ->paginate($perPage);

        return $this->ok(VoucherResource::collection($vouchers));
    }

    public function show(Voucher $voucher): JsonResponse
    {
        return $this->ok(new VoucherResource($voucher));
    }

    public function store(StoreVoucherRequest $request): JsonResponse
    {
        $voucher = VoucherService::record($request->validated(), $request->user()->id);

        return $this->created(new VoucherResource($voucher));
    }
}
