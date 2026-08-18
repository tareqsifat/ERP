<?php

namespace Modules\Order\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Party\App\Http\Resources\PartyResource;
use Modules\User\App\Http\Resources\UserResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_no' => $this->order_no,
            'party' => new PartyResource($this->whenLoaded('party')),
            'party_id' => $this->party_id,
            'merchandiser' => new UserResource($this->whenLoaded('merchandiser')),
            'merchandiser_id' => $this->merchandiser_id,
            'item_image_path' => $this->item_image_path,
            'title' => $this->title,
            'description' => $this->description,
            'fabrication' => $this->fabrication,
            'gsm' => $this->gsm,
            'yarn_count' => $this->yarn_count,
            'shipment_mode' => $this->shipment_mode,
            'payment_mode' => $this->payment_mode,
            'bank_account_name' => $this->bank_account_name,
            'year' => $this->year,
            'season' => $this->season,
            'pantone' => $this->pantone,
            'consignee' => $this->consignee,
            'notify_party' => $this->notify_party,
            'contract_date' => $this->contract_date,
            'expiry_date' => $this->expiry_date,
            'negotiation_period_days' => $this->negotiation_period_days,
            'port_of_loading' => $this->port_of_loading,
            'port_of_discharge' => $this->port_of_discharge,
            'payment_terms' => $this->payment_terms,
            'remarks' => $this->remarks,
            'status' => $this->status,
            'grand_total' => $this->grand_total,
            'line_items' => OrderLineItemResource::collection($this->whenLoaded('lineItems')),
            'created_at' => $this->created_at,
        ];
    }
}
