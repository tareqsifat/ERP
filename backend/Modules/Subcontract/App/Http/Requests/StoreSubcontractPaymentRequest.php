<?php

namespace Modules\Subcontract\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubcontractPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:subcontract.{outward,inward}.manage` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'occurred_on' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
