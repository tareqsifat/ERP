<?php

namespace Modules\Subcontract\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubcontractOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subcontract_no' => $this->subcontract_no,
            'direction' => $this->direction,
            'party_id' => $this->party_id,
            'order_id' => $this->order_id,
            'style' => $this->style,
            'color' => $this->color,
            'size' => $this->size,
            'rate' => $this->rate,
            'rate_unit' => $this->rate_unit,
            'quantity_expected' => $this->quantity_expected,
            'raw_material_id' => $this->raw_material_id,
            'raw_material_quantity' => $this->raw_material_quantity,
            'location_id' => $this->location_id,
            'expected_date' => $this->expected_date,
            'status' => $this->status,
            'job_work_income_amount' => $this->job_work_income_amount,
            'dispatched_back_at' => $this->dispatched_back_at,
            'remarks' => $this->remarks,
            'pieces' => SubcontractOrderPieceResource::collection($this->whenLoaded('pieces')),
            'ledger_entries' => SubcontractLedgerEntryResource::collection($this->whenLoaded('ledgerEntries')),
            'created_at' => $this->created_at,
        ];
    }
}
