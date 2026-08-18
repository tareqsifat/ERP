<?php

namespace Modules\Shipment\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\User\App\Http\Resources\UserResource;

class ShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_no' => $this->invoice_no,
            'order_id' => $this->order_id,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_by' => $this->created_by,
            'total_quantity' => $this->total_quantity,
            'total_cbm' => $this->total_cbm,
            'shipment_date' => $this->shipment_date,
            'status' => $this->status,
            'remarks' => $this->remarks,
            'created_at' => $this->created_at,
        ];
    }
}
