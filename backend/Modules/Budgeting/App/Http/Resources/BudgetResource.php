<?php

namespace Modules\Budgeting\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BudgetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'style' => $this->style,
            'budgeted_quantity' => $this->budgeted_quantity,
            'average_unit_price' => $this->average_unit_price,
            'total_value' => $this->total_value,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
