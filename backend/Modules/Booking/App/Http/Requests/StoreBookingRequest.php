<?php

namespace Modules\Booking\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:booking.create` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', Rule::exists('orders', 'id')->whereNull('deleted_at')],
            'preparer_id' => ['required', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'booking_date' => ['required', 'date'],
            'composition' => ['nullable', 'string', 'max:2000'],
            'process_loss_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'other_fabrics' => ['nullable', 'string', 'max:2000'],
            'rib' => ['nullable', 'string', 'max:255'],
            'collar' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['draft', 'confirmed', 'cancelled'])],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],

            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.style' => ['required', 'string', 'max:255'],
            'line_items.*.color' => ['required', 'string', 'max:100'],
            'line_items.*.shipment_date' => ['nullable', 'date'],
            'line_items.*.quantity' => ['required', 'integer', 'min:1'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'line_items.*.garment_description' => ['nullable', 'string', 'max:2000'],
            'line_items.*.pantone' => ['nullable', 'string', 'max:50'],
            'line_items.*.body_fabrication' => ['nullable', 'string', 'max:255'],
            'line_items.*.yarn_count' => ['nullable', 'string', 'max:50'],
            'line_items.*.dzn_quantity' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.gray_fabric_consumption_kg' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.rib_consumption_kg' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
