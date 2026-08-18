<?php

namespace Modules\Accounting\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountingCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:accounting.voucher.create` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::in(['income', 'expense'])],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }
}
