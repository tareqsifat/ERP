<?php

namespace Modules\Location\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Location\Database\Factories\StockTransferFactory;
use Modules\Order\App\Models\Order;

// transfer_no/year/sequence_no/status/received_* absent from #[Fillable]
// — only App\Services\StockTransferService writes them (dispatch/receive
// are the only two ways this row is ever created or changed).
#[Fillable([
    'from_location_id', 'to_location_id', 'order_id', 'style', 'color', 'size',
    'quantity_dispatched', 'dispatched_by', 'dispatched_at', 'remarks',
])]
class StockTransfer extends Model
{
    /** @use HasFactory<StockTransferFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'dispatched_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    protected static function newFactory(): StockTransferFactory
    {
        return StockTransferFactory::new();
    }

    public function fromLocation()
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation()
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function dispatchedBy()
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
