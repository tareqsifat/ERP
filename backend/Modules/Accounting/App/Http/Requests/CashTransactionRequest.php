<?php

namespace Modules\Accounting\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Shared by CashController's increase/reduce (adjust) actions.
class CashTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:accounting.cash.manage` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
