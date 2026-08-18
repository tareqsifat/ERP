<?php

namespace Modules\Sampling\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSampleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:sampling.edit` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'order_id' => ['sometimes', 'required', 'integer', Rule::exists('orders', 'id')->whereNull('deleted_at')],
            'consignee' => ['nullable', 'string', 'max:255'],
            'style_number' => ['nullable', 'string', 'max:255'],
            'item' => ['nullable', 'string', 'max:255'],
            'sample_type' => ['nullable', Rule::in(['proto', 'fit', 'pp', 'size_set', 'shipment', 'salesman'])],
            'quantity' => ['sometimes', 'required', 'integer', 'min:1'],
            'status' => ['sometimes', Rule::in(['requested', 'sent', 'approved', 'rejected'])],
        ];
    }
}
