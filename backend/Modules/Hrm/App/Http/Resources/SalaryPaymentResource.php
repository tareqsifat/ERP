<?php

namespace Modules\Hrm\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'month' => $this->month,
            'year' => $this->year,
            'salary_amount' => $this->salary_amount,
            'paid_amount' => $this->paid_amount,
            'due_amount' => $this->due_amount, // accessor, see SalaryPayment::getDueAmountAttribute()
            'payment_method' => $this->payment_method,
            'pay_date' => $this->pay_date,
            'created_at' => $this->created_at,
        ];
    }
}
