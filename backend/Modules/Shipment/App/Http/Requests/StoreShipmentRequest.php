<?php

namespace Modules\Shipment\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:shipment.create` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', Rule::exists('orders', 'id')->whereNull('deleted_at')],
            'total_quantity' => ['required', 'integer', 'min:1'],
            'total_cbm' => ['nullable', 'numeric', 'min:0'],
            'shipment_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['draft', 'shipped', 'delivered'])],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
