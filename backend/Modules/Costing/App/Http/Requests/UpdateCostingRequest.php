<?php

namespace Modules\Costing\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:costing.edit` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'order_id' => ['sometimes', 'required', 'integer', Rule::exists('orders', 'id')->whereNull('deleted_at')],
            'style' => ['nullable', 'string', 'max:255'],
            'costed_quantity' => ['sometimes', 'required', 'integer', 'min:1'],
            'average_unit_cost' => ['sometimes', 'required', 'numeric', 'min:0'],
            'status' => ['sometimes', Rule::in(['draft', 'approved'])],
        ];
    }
}
