<?php

namespace Modules\Accounting\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Shared by BankAccountController's deposit/withdraw actions.
class BankTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:accounting.bank.manage` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
