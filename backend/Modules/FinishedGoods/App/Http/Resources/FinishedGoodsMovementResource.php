<?php

namespace Modules\FinishedGoods\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinishedGoodsMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'location_id' => $this->location_id,
            'order_id' => $this->order_id,
            'style' => $this->style,
            'color' => $this->color,
            'size' => $this->size,
            'piece_serial_id' => $this->piece_serial_id,
            'quantity' => $this->quantity,
            'type' => $this->type,
            'occurred_on' => $this->occurred_on,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
        ];
    }
}
