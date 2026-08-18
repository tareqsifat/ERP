<?php

namespace Modules\RawMaterial\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Location\App\Models\Location;
use Modules\Party\App\Models\Party;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:raw-material.purchase-order.manage` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'supplier_id' => [
                'required', 'integer', Rule::exists('parties', 'id')->whereNull('deleted_at'),
                function ($attribute, $value, $fail) {
                    if (Party::find($value)?->type !== 'supplier') {
                        $fail('The selected party must be a Supplier.');
                    }
                },
            ],
            'location_id' => [
                'required', 'integer', Rule::exists('locations', 'id')->whereNull('deleted_at'),
                function ($attribute, $value, $fail) {
                    $location = Location::find($value);
                    if ($location && ! in_array($location->type, ['factory', 'store'], true)) {
                        $fail('Purchase orders can only be received into a Factory or Store location.');
                    }
                },
            ],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'remarks' => ['nullable', 'string', 'max:2000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.raw_material_id' => ['required', 'integer', 'exists:raw_materials,id'],
            'items.*.quantity_ordered' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
