<?php

namespace Modules\Production\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BundleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cut_ticket_id' => $this->cut_ticket_id,
            'bundle_no' => $this->bundle_no,
            'quantity' => $this->quantity,
            'line_id' => $this->line_id,
            'status' => $this->status,
            'assigned_to_line_at' => $this->assigned_to_line_at,
            'line_output_at' => $this->line_output_at,
            'piece_serials' => PieceSerialResource::collection($this->whenLoaded('pieceSerials')),
        ];
    }
}
