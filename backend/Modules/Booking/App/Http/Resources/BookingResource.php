<?php

namespace Modules\Booking\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\User\App\Http\Resources\UserResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'preparer' => new UserResource($this->whenLoaded('preparer')),
            'preparer_id' => $this->preparer_id,
            'booking_date' => $this->booking_date,
            'composition' => $this->composition,
            'process_loss_percent' => $this->process_loss_percent,
            'other_fabrics' => $this->other_fabrics,
            'rib' => $this->rib,
            'collar' => $this->collar,
            'item_image_path' => $this->item_image_path,
            'status' => $this->status,
            'line_items' => BookingLineItemResource::collection($this->whenLoaded('lineItems')),
            'created_at' => $this->created_at,
        ];
    }
}
