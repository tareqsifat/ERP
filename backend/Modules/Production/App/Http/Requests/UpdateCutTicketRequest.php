<?php

namespace Modules\Production\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Location\App\Models\Location;

/**
 * Only reachable while the Cut Ticket is still `draft` — see
 * CutTicketController::update(). Once finalized, fabric has been
 * deducted and serials generated, so nothing here may change.
 */
class UpdateCutTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:production.cutting.create` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'style' => ['sometimes', 'string', 'max:255'],
            'color' => ['sometimes', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:50'],
            'cut_date' => ['sometimes', 'date'],
            'cutting_master_id' => ['sometimes', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'raw_material_id' => ['sometimes', 'integer', Rule::exists('raw_materials', 'id')->whereNull('deleted_at')],
            'fabric_consumed' => ['sometimes', 'numeric', 'min:0.001'],
            'location_id' => [
                'sometimes', 'integer', Rule::exists('locations', 'id')->whereNull('deleted_at'),
                function ($attribute, $value, $fail) {
                    if (Location::find($value)?->type !== 'factory') {
                        $fail('Cutting can only happen at a Factory location.');
                    }
                },
            ],
            'bundle_size' => ['sometimes', 'integer', 'min:1'],
            'planned_quantity' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
