<?php

namespace Modules\Party\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            // total_bill/advance/paid/due/balance: deliberately not
            // returned yet — Party.php docblock + README explain why
            // (computed from Modules/Accounting vouchers, Phase 6).
        ];
    }
}
