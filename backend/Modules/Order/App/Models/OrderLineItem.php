<?php

namespace Modules\Order\App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * `total_price` is deliberately absent from #[Fillable] — OrderController
 * always computes it server-side as quantity * unit_price before saving,
 * never trusting a client-sent total (failed_doc.md money-handling rule).
 */
#[Fillable(['order_id', 'style', 'color', 'item', 'shipment_date', 'quantity', 'unit_price'])]
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
