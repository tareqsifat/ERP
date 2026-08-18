<?php

namespace Modules\Accounting\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Accounting\App\Services\BankLedgerService;

class BankAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account_holder_name' => $this->account_holder_name,
            'bank_name' => $this->bank_name,
            'account_number' => $this->account_number,
            'branch_name' => $this->branch_name,
            'routing_swift_no' => $this->routing_swift_no,
            'is_active' => $this->is_active,
            'balance' => BankLedgerService::balanceOf($this->resource),
            'created_at' => $this->created_at,
        ];
    }
}
