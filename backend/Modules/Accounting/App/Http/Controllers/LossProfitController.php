<?php

namespace Modules\Accounting\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Accounting\App\Services\LossProfitService;

// PRD v1 §3.13/§4.12 — Loss & Profit, year-filterable summary cards.
class LossProfitController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $year = (int) $request->integer('year', now()->year);

        return $this->ok(LossProfitService::summarize($year));
    }
}
