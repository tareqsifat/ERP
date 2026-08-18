<?php

namespace Modules\Location\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transfer_no' => $this->transfer_no,
            'from_location_id' => $this->from_location_id,
            'to_location_id' => $this->to_location_id,
            'order_id' => $this->order_id,
            'style' => $this->style,
            'color' => $this->color,
            'size' => $this->size,
            'quantity_dispatched' => $this->quantity_dispatched,
            'quantity_received' => $this->quantity_received,
            'status' => $this->status,
            'dispatched_by' => $this->dispatched_by,
            'dispatched_at' => $this->dispatched_at,
            'received_by' => $this->received_by,
            'received_at' => $this->received_at,
            'remarks' => $this->remarks,
        ];
    }
}
