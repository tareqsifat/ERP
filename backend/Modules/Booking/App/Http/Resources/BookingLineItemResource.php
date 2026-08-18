<?php

namespace Modules\Booking\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingLineItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'style' => $this->style,
            'color' => $this->color,
            'shipment_date' => $this->shipment_date,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total_value' => $this->total_value,
            'garment_description' => $this->garment_description,
            'garment_picture_path' => $this->garment_picture_path,
            'pantone' => $this->pantone,
            'body_fabrication' => $this->body_fabrication,
            'yarn_count' => $this->yarn_count,
            'dzn_quantity' => $this->dzn_quantity,
            'gray_fabric_consumption_kg' => $this->gray_fabric_consumption_kg,
            'rib_consumption_kg' => $this->rib_consumption_kg,
        ];
    }
}
