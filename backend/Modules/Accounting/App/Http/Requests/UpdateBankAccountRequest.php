<?php

namespace Modules\Accounting\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:accounting.bank.manage` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'account_holder_name' => ['sometimes', 'string', 'max:255'],
            'bank_name' => ['sometimes', 'string', 'max:255'],
            'account_number' => ['sometimes', 'string', 'max:100'],
            'branch_name' => ['nullable', 'string', 'max:255'],
            'routing_swift_no' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }
}
