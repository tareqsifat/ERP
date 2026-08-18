<?php

namespace Modules\Party\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Accounting\App\Services\PartyFinancialsService;

class PartyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'country' => $this->country,
            'opening_balance_type' => $this->opening_balance_type,
            'opening_balance' => $this->opening_balance,
            'remarks' => $this->remarks,
            'image_path' => $this->image_path,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            // Phase 6: total_bill/paid/advance/due/balance, computed from
            // Modules/Accounting's PartyBill/Voucher ledgers — see
            // Party.php's docblock and App\Services\PartyFinancialsService.
            'financials' => PartyFinancialsService::summarize($this->resource),
        ];
    }
}
