<?php

namespace Modules\Sampling\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSampleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:sampling.create` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', Rule::exists('orders', 'id')->whereNull('deleted_at')],
            'consignee' => ['nullable', 'string', 'max:255'],
            'style_number' => ['nullable', 'string', 'max:255'],
            'item' => ['nullable', 'string', 'max:255'],
            'sample_type' => ['nullable', Rule::in(['proto', 'fit', 'pp', 'size_set', 'shipment', 'salesman'])],
            'quantity' => ['required', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in(['requested', 'sent', 'approved', 'rejected'])],
        ];
    }
}
