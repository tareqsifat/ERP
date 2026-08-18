<?php

namespace Modules\Budgeting\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:budgeting.create` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', Rule::exists('orders', 'id')->whereNull('deleted_at')],
            'style' => ['nullable', 'string', 'max:255'],
            'budgeted_quantity' => ['required', 'integer', 'min:1'],
            'average_unit_price' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['draft', 'approved'])],
        ];
    }
}
