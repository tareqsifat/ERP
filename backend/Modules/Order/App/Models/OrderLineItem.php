<?php

namespace Modules\Order\App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * `total_price` IS in #[Fillable] (Phase 4 correction — excluding it here
 * previously broke every order create, since Eloquent's default fill()
 * silently drops non-fillable keys instead of erroring; caught once
 * AppServiceProvider::preventSilentlyDiscardingAttributes(true) was added
 * and every affected create() started throwing). The real defense against
 * a client-supplied total was never this attribute list — it's that
 * StoreOrderRequest/UpdateOrderRequest's validation rules never accept
 * `line_items.*.total_price` at all, so `$request->validated()` can never
 * contain a client-controlled total in the first place. OrderController
 * always computes it server-side as quantity * unit_price before calling
 * create().
 */
#[Fillable(['order_id', 'style', 'color', 'item', 'shipment_date', 'quantity', 'unit_price', 'total_price'])]
class OrderLineItem extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'shipment_date' => 'date',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
