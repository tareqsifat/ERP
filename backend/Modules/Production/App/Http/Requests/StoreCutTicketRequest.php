<?php

namespace Modules\Production\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Location\App\Models\Location;

class StoreCutTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:production.cutting.create` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', Rule::exists('orders', 'id')->whereNull('deleted_at')],
            'booking_id' => ['nullable', 'integer', Rule::exists('bookings', 'id')->whereNull('deleted_at')],
            'style' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:50'],
            'cut_date' => ['required', 'date'],
            'cutting_master_id' => ['required', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'raw_material_id' => ['required', 'integer', Rule::exists('raw_materials', 'id')->whereNull('deleted_at')],
            'fabric_consumed' => ['required', 'numeric', 'min:0.001'],
            'location_id' => [
                'required', 'integer', Rule::exists('locations', 'id')->whereNull('deleted_at'),
                function ($attribute, $value, $fail) {
                    if (Location::find($value)?->type !== 'factory') {
                        $fail('Cutting can only happen at a Factory location.');
                    }
                },
            ],
            'bundle_size' => ['required', 'integer', 'min:1'],
            'planned_quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
