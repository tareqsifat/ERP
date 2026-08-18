<?php

namespace Modules\RawMaterial\App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// `total_price` IS in #[Fillable] (see Order/App/Models/OrderLineItem.php's
// docblock for the general rule) — PurchaseOrderController computes it
// server-side before create(); StorePurchaseOrderRequest never validates
// a client-sent total. `quantity_received` stays OUT of #[Fillable]: it is
// never written via create()/fill(), only via direct property assignment
// in PurchaseOrderController@receive ($item->quantity_received = ...).
#[Fillable(['purchase_order_id', 'raw_material_id', 'quantity_ordered', 'unit_price', 'total_price'])]
class RawMaterialPurchaseOrderItem extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'quantity_ordered' => 'decimal:3',
            'quantity_received' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(RawMaterialPurchaseOrder::class, 'purchase_order_id');
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function outstandingQuantity(): string
    {
        return bcsub((string) $this->quantity_ordered, (string) $this->quantity_received, 3);
    }
}
