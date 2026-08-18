<?php

namespace Modules\Booking\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Booking\Database\Factories\BookingFactory;
use Modules\Order\App\Models\Order;

#[Fillable([
    'order_id', 'preparer_id', 'booking_date', 'composition', 'process_loss_percent',
    'other_fabrics', 'rib', 'collar', 'item_image_path', 'status',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'process_loss_percent' => 'decimal:2',
        ];
    }

    protected static function newFactory(): BookingFactory
    {
        return BookingFactory::new();
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function preparer()
    {
        return $this->belongsTo(User::class, 'preparer_id');
    }

    public function lineItems()
    {
        return $this->hasMany(BookingLineItem::class);
    }
}
