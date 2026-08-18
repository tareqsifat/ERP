<?php

namespace Modules\FinishedGoods\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Modules\Location\App\Models\Location;
use Modules\Order\App\Models\Order;
use Modules\Production\App\Models\PieceSerial;

/**
 * Immutable ledger row — same append-only contract as
 * Modules\RawMaterial\App\Models\RawMaterialStockMovement. Only ever
 * created through App\Services\FinishedGoodsStockService.
 */
#[Fillable([
    'location_id', 'order_id', 'style', 'color', 'size', 'piece_serial_id',
    'quantity', 'type', 'reference_type', 'reference_id', 'occurred_on', 'created_by',
])]
class FinishedGoodsMovement extends Model
{
    protected function casts(): array
    {
        return ['occurred_on' => 'date'];
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function pieceSerial()
    {
        return $this->belongsTo(PieceSerial::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
