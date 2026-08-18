<?php

namespace Modules\Shipment\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:shipment.edit` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'total_quantity' => ['sometimes', 'required', 'integer', 'min:1'],
            'total_cbm' => ['nullable', 'numeric', 'min:0'],
            'shipment_date' => ['nullable', 'date'],
            'status' => ['sometimes', Rule::in(['draft', 'shipped', 'delivered'])],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
