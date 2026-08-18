<?php

namespace Modules\Subcontract\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubcontractOrderPieceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'piece_serial_id' => $this->piece_serial_id,
            'serial' => $this->whenLoaded('pieceSerial', fn () => $this->pieceSerial->serial),
            'issued_at' => $this->issued_at,
            'resolution' => $this->resolution,
            'resolved_at' => $this->resolved_at,
        ];
    }
}
