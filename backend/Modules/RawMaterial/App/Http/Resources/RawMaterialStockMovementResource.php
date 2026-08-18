<?php

namespace Modules\RawMaterial\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RawMaterialStockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'raw_material_id' => $this->raw_material_id,
            'location_id' => $this->location_id,
            'type' => $this->type,
            'quantity' => $this->quantity,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'occurred_on' => $this->occurred_on,
            'created_by' => $this->created_by,
            'remarks' => $this->remarks,
            'created_at' => $this->created_at,
        ];
    }
}
