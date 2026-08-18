<?php

namespace Modules\Production\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CutTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'booking_id' => $this->booking_id,
            'style' => $this->style,
            'color' => $this->color,
            'size' => $this->size,
            'cut_date' => $this->cut_date,
            'cutting_master_id' => $this->cutting_master_id,
            'raw_material_id' => $this->raw_material_id,
            'fabric_consumed' => $this->fabric_consumed,
            'location_id' => $this->location_id,
            'bundle_size' => $this->bundle_size,
            'planned_quantity' => $this->planned_quantity,
            'status' => $this->status,
            'finalized_at' => $this->finalized_at,
            'bundles' => BundleResource::collection($this->whenLoaded('bundles')),
            'created_at' => $this->created_at,
        ];
    }
}
