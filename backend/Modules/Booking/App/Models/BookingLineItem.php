<?php

namespace Modules\Booking\App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// `total_value` IS in #[Fillable] — see Order/App/Models/OrderLineItem.php's
// docblock for why (Phase 4 correction: the real defense is that
// StoreBookingRequest never validates a client-sent total_value, not this
// attribute list, which previously excluded it and broke every create()).
#[Fillable([
    'booking_id', 'style', 'color', 'shipment_date', 'quantity', 'unit_price', 'total_value',
    'garment_description', 'garment_picture_path', 'pantone', 'body_fabrication',
    'yarn_count', 'dzn_quantity', 'gray_fabric_consumption_kg', 'rib_consumption_kg',
])]
class BookingLineItem extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'shipment_date' => 'date',
            'unit_price' => 'decimal:2',
            'total_value' => 'decimal:2',
            'dzn_quantity' => 'decimal:2',
            'gray_fabric_consumption_kg' => 'decimal:2',
            'rib_consumption_kg' => 'decimal:2',
        ];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
