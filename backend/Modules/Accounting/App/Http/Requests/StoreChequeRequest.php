<?php

namespace Modules\Accounting\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChequeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:accounting.cheque.manage` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'party_id' => ['nullable', 'integer', Rule::exists('parties', 'id')->whereNull('deleted_at')],
            'bank_account_id' => ['required', 'integer', Rule::exists('bank_accounts', 'id')->whereNull('deleted_at')],
            'cheque_no' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'issue_date' => ['required', 'date'],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
