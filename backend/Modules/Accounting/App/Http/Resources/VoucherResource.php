<?php

namespace Modules\Accounting\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'voucher_no' => $this->voucher_no,
            'type' => $this->type,
            'purpose' => $this->purpose,
            'party_id' => $this->party_id,
            'category_id' => $this->category_id,
            'amount' => $this->amount,
            'payment_type' => $this->payment_type,
            'bank_account_id' => $this->bank_account_id,
            'cheque_id' => $this->cheque_id,
            'date' => $this->date,
            'bill_no' => $this->bill_no,
            'remarks' => $this->remarks,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
        ];
    }
}
