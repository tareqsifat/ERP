<?php

namespace Modules\Sampling\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SampleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'consignee' => $this->consignee,
            'style_number' => $this->style_number,
            'item' => $this->item,
            'sample_type' => $this->sample_type,
            'quantity' => $this->quantity,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
