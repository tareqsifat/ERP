<?php

namespace Modules\Costing\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CostingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'style' => $this->style,
            'costed_quantity' => $this->costed_quantity,
            'average_unit_cost' => $this->average_unit_cost,
            'total_cost' => $this->total_cost,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
