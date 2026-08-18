<?php

namespace Modules\Accounting\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChequeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'party_id' => $this->party_id,
            'bank_account_id' => $this->bank_account_id,
            'cheque_no' => $this->cheque_no,
            'amount' => $this->amount,
            'issue_date' => $this->issue_date,
            'type' => $this->type,
            'status' => $this->status,
            'passed_at' => $this->passed_at,
            'remarks' => $this->remarks,
            'created_at' => $this->created_at,
        ];
    }
}
