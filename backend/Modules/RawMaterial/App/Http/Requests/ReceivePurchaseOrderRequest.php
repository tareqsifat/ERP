<?php

namespace Modules\RawMaterial\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceivePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:raw-material.purchase-order.manage` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            // Partial receipt supported (PRD v2 §3.19) — only the items
            // present here get a movement posted; omitted items are left
            // untouched for a later receipt against the same PO.
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer', 'exists:raw_material_purchase_order_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
        ];
    }
}
