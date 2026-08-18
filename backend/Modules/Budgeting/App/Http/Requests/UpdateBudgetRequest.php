<?php

namespace Modules\Budgeting\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:budgeting.edit` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'order_id' => ['sometimes', 'required', 'integer', Rule::exists('orders', 'id')->whereNull('deleted_at')],
            'style' => ['nullable', 'string', 'max:255'],
            'budgeted_quantity' => ['sometimes', 'required', 'integer', 'min:1'],
            'average_unit_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'status' => ['sometimes', Rule::in(['draft', 'approved'])],
        ];
    }
}
