<?php

namespace Modules\Subcontract\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubcontractOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:subcontract.{outward,inward}.manage` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'direction' => ['required', Rule::in(['outward', 'inward'])],
            'party_id' => [
                'required', 'integer',
                Rule::exists('parties', 'id')->whereNull('deleted_at')->where('type', 'subcontractor'),
            ],
            'order_id' => ['nullable', 'integer', Rule::exists('orders', 'id')->whereNull('deleted_at')],
            'style' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:50'],
            'rate' => ['required', 'numeric', 'min:0.01'],
            'rate_unit' => ['required', Rule::in(['piece', 'dozen'])],
            'quantity_expected' => ['required', 'integer', 'min:1'],
            'raw_material_id' => ['nullable', 'integer', Rule::exists('raw_materials', 'id')->whereNull('deleted_at')],
            'raw_material_quantity' => ['nullable', 'numeric', 'min:0.001'],
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->whereNull('deleted_at')],
            'expected_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // PRD v2 §3.23: Outward references one of our own Orders being
            // subcontracted out. Inward leaves this optional (see the
            // subcontract_orders migration's docblock).
            if ($this->input('direction') === 'outward' && ! $this->filled('order_id')) {
                $validator->errors()->add('order_id', 'An Order is required for an Outward subcontract order.');
            }

            if ($this->filled('raw_material_id') && ! $this->filled('raw_material_quantity')) {
                $validator->errors()->add('raw_material_quantity', 'A quantity is required when a raw material is set.');
            }
        });
    }
}
