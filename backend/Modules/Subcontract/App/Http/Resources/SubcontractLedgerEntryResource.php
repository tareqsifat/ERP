<?php

namespace Modules\Subcontract\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubcontractLedgerEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subcontract_order_id' => $this->subcontract_order_id,
            'party_id' => $this->party_id,
            'type' => $this->type,
            'amount' => $this->amount,
            'occurred_on' => $this->occurred_on,
            'remarks' => $this->remarks,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
        ];
    }
}
