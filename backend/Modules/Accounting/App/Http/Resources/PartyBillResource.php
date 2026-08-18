<?php

namespace Modules\Accounting\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartyBillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'party_id' => $this->party_id,
            'amount' => $this->amount,
            'bill_date' => $this->bill_date,
            'description' => $this->description,
            'reference' => $this->reference,
            'created_at' => $this->created_at,
        ];
    }
}
