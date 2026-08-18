<?php

namespace Modules\Production\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Booking\App\Models\Booking;
use Modules\Location\App\Models\Location;
use Modules\Order\App\Models\Order;
use Modules\Production\Database\Factories\CutTicketFactory;
use Modules\RawMaterial\App\Models\RawMaterial;

// status/finalized_at absent from #[Fillable] — only
// App\Services\CuttingService::finalize() writes them.
#[Fillable([
    'order_id', 'booking_id', 'style', 'color', 'size', 'cut_date', 'cutting_master_id',
    'raw_material_id', 'fabric_consumed', 'location_id', 'bundle_size', 'planned_quantity',
])]
class CutTicket extends Model
{
    /** @use HasFactory<CutTicketFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'cut_date' => 'date',
            'fabric_consumed' => 'decimal:3',
            'finalized_at' => 'datetime',
        ];
    }

    protected static function newFactory(): CutTicketFactory
    {
        return CutTicketFactory::new();
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function cuttingMaster()
    {
        return $this->belongsTo(User::class, 'cutting_master_id');
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function bundles()
    {
        return $this->hasMany(Bundle::class);
    }
}
