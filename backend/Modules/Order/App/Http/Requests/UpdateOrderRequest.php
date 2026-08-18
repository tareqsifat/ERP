<?php

namespace Modules\Order\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Party\App\Models\Party;

/**
 * PRD v1 §4.3. `line_items` is optional here (unlike Store): a PUT to
 * /orders/{order} can update header fields only, or replace the full
 * line-item set if `line_items` is present — see
 * OrderController@update / README "Line item update semantics".
 */
class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:order.edit` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'party_id' => [
                'sometimes', 'required', 'integer',
                Rule::exists('parties', 'id')->whereNull('deleted_at'),
                function ($attribute, $value, $fail) {
                    $party = Party::find($value);
                    if ($party && $party->type !== 'buyer') {
                        $fail('The selected party must be a Buyer.');
                    }
                },
            ],
            'merchandiser_id' => ['sometimes', 'required', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'fabrication' => ['nullable', 'string', 'max:255'],
            'gsm' => ['nullable', 'string', 'max:50'],
            'yarn_count' => ['nullable', 'string', 'max:50'],
            'shipment_mode' => ['sometimes', 'required', Rule::in(['sea', 'air', 'sea_air', 'road', 'courier'])],
            'payment_mode' => ['sometimes', 'required', Rule::in(['lc', 'tt', 'advance', 'on_delivery'])],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'season' => ['nullable', 'string', 'max:100'],
            'pantone' => ['nullable', 'string', 'max:50'],
            'consignee' => ['nullable', 'string', 'max:2000'],
            'notify_party' => ['nullable', 'string', 'max:2000'],
            'contract_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:contract_date'],
            'negotiation_period_days' => ['nullable', 'integer', 'min:0'],
            'port_of_loading' => ['nullable', 'string', 'max:255'],
            'port_of_discharge' => ['nullable', 'string', 'max:255'],
            'payment_terms' => ['nullable', 'string', 'max:2000'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'status' => ['sometimes', 'required', Rule::in(['pending', 'approved', 'in_production', 'shipped', 'completed', 'cancelled'])],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],

            'line_items' => ['sometimes', 'array', 'min:1'],
            'line_items.*.style' => ['required', 'string', 'max:255'],
            'line_items.*.color' => ['required', 'string', 'max:100'],
            'line_items.*.item' => ['required', 'string', 'max:255'],
            'line_items.*.shipment_date' => ['nullable', 'date'],
            'line_items.*.quantity' => ['required', 'integer', 'min:1'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
