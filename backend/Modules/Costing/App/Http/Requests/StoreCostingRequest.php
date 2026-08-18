<?php

namespace Modules\Costing\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:costing.create` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', Rule::exists('orders', 'id')->whereNull('deleted_at')],
            'style' => ['nullable', 'string', 'max:255'],
            'costed_quantity' => ['required', 'integer', 'min:1'],
            'average_unit_cost' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['draft', 'approved'])],
        ];
    }
}
