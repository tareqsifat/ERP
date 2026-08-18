<?php

namespace Modules\Production\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PieceSerialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bundle_id' => $this->bundle_id,
            'order_id' => $this->order_id,
            'serial' => $this->serial,
            'status' => $this->status,
            'qc_reject_reason' => $this->qc_reject_reason,
            'qc_by' => $this->qc_by,
            'qc_at' => $this->qc_at,
            'created_at' => $this->created_at,
        ];
    }
}
