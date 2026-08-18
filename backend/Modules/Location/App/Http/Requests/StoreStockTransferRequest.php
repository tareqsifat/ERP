<?php

namespace Modules\Location\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:stock-transfer.dispatch` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'from_location_id' => ['required', 'integer', Rule::exists('locations', 'id')->whereNull('deleted_at')],
            'to_location_id' => ['required', 'integer', 'different:from_location_id', Rule::exists('locations', 'id')->whereNull('deleted_at')],
            'order_id' => ['required', 'integer', Rule::exists('orders', 'id')->whereNull('deleted_at')],
            'style' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:50'],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
