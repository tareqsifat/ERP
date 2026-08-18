<?php

namespace Modules\Hrm\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaySalaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:hrm.salary.pay` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:100'],
            'pay_date' => ['nullable', 'date'],
        ];
    }
}
