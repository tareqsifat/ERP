<?php

namespace Modules\Production\App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Production\App\Models\Bundle;
use Modules\Production\App\Models\Line;

/**
 * PRD v2 §3.18: "Line Supervisors log daily line input (bundles received
 * into a line) and output (bundles/pieces completed)." Simplified for v1
 * (documented in README "Known simplifications") to two lifecycle
 * timestamps per bundle rather than a full recurring daily-log ledger —
 * a bundle is assigned to a line once, then logged as output once, which
 * covers the traceability requirement without a bookkeeping table for
 * an event that in practice happens twice per bundle.
 */
class SewingService
{
    public static function assignToLine(Bundle $bundle, Line $line): Bundle
    {
        if ($bundle->status !== 'cut') {
            throw ValidationException::withMessages([
                'status' => "Bundle {$bundle->bundle_no} is not in 'cut' status.",
            ]);
        }

        return DB::transaction(function () use ($bundle, $line) {
            $bundle->line_id = $line->id;
            $bundle->status = 'in_sewing';
            $bundle->assigned_to_line_at = now();
            $bundle->save();

            $bundle->pieceSerials()->update(['status' => 'in_sewing']);

            return $bundle;
        });
    }

    public static function logOutput(Bundle $bundle): Bundle
    {
        if ($bundle->status !== 'in_sewing') {
            throw ValidationException::withMessages([
                'status' => "Bundle {$bundle->bundle_no} is not currently in sewing.",
            ]);
        }

        return DB::transaction(function () use ($bundle) {
            $bundle->status = 'sewn';
            $bundle->line_output_at = now();
            $bundle->save();

            $bundle->pieceSerials()->update(['status' => 'sewn']);

            return $bundle;
        });
    }
}
