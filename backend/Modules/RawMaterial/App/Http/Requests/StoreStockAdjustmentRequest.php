<?php

namespace Modules\RawMaterial\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Location\App\Models\Location;

/**
 * Manual correction to the raw material ledger — see
 * App\Services\RawMaterialStockService::adjustment(). `quantity` here is
 * signed (positive = correcting stock upward, negative = downward); the
 * service stores it as-is under type=adjustment.
 */
class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:raw-material.edit` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'raw_material_id' => ['required', 'integer', 'exists:raw_materials,id'],
            'location_id' => [
                'required', 'integer', 'exists:locations,id',
                function ($attribute, $value, $fail) {
                    // Mirrors RawMaterialStockService's own guard — surfaced
                    // here as a proper 422 instead of letting the service
                    // throw and 500.
                    $location = Location::find($value);
                    if ($location && ! in_array($location->type, ['factory', 'store'], true)) {
                        $fail('Raw material stock is only tracked at Factory or Store locations.');
                    }
                },
            ],
            'quantity' => ['required', 'numeric', 'not_in:0'],
            'remarks' => ['required', 'string', 'max:1000'],
        ];
    }
}
