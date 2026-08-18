<?php

namespace Modules\Shipment\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Order\App\Models\Order;
use Modules\Shipment\Database\Factories\ShipmentFactory;

/**
 * `invoice_no`/`year`/`sequence_no` are absent from #[Fillable] — only
 * ShipmentInvoiceNumberGenerator (via ShipmentController) sets them.
 */
#[Fillable(['order_id', 'created_by', 'total_quantity', 'total_cbm', 'shipment_date', 'status', 'remarks'])]
class Shipment extends Model
{
    /** @use HasFactory<ShipmentFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'shipment_date' => 'date',
            'total_cbm' => 'decimal:3',
        ];
    }

    protected static function newFactory(): ShipmentFactory
    {
        return ShipmentFactory::new();
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
