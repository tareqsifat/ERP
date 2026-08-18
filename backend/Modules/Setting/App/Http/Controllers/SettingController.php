<?php

namespace Modules\Setting\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Setting\App\Http\Requests\UpdateSettingRequest;
use Modules\Setting\App\Services\SettingService;

/**
 * PRD v1 §3.15/§4.13 — Currency Settings, Notifications, System Settings
 * (multi-tab), Company Settings. One page, four tabs, each backed by the
 * same key/value store (App\Services\SettingService).
 */
class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->ok(SettingService::allGrouped());
    }

    /**
     * Bulk upserts every {key: value} pair in `values` under `group`,
     * e.g. {"group": "currency", "values": {"code": "BDT", "symbol": "৳"}}
     * upserts `currency.code` and `currency.symbol`.
     */
    public function update(UpdateSettingRequest $request): JsonResponse
    {
        $data = $request->validated();

        foreach ($data['values'] as $shortKey => $value) {
            SettingService::set("{$data['group']}.{$shortKey}", $value, $data['group'], $request->user()->id);
        }

        return $this->ok(SettingService::allGrouped());
    }
}
