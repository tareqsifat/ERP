<?php

namespace Modules\RawMaterial\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Location\App\Models\Location;
use Modules\Party\App\Models\Party;
use Modules\RawMaterial\Database\Factories\RawMaterialPurchaseOrderFactory;

// po_no/year/sequence_no absent from #[Fillable] — only
// PurchaseOrderNumberGenerator (via PurchaseOrderController) sets them.
#[Fillable(['supplier_id', 'location_id', 'status', 'order_date', 'expected_date', 'created_by', 'remarks'])]
class RawMaterialPurchaseOrder extends Model
{
    /** @use HasFactory<RawMaterialPurchaseOrderFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_date' => 'date',
        ];
    }

    protected static function newFactory(): RawMaterialPurchaseOrderFactory
    {
        return RawMaterialPurchaseOrderFactory::new();
    }

    public function supplier()
    {
        return $this->belongsTo(Party::class, 'supplier_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(RawMaterialPurchaseOrderItem::class, 'purchase_order_id');
    }

    /**
     * Recomputes status from item receipt progress — called after every
     * receipt posting (see PurchaseOrderReceiptService).
     */
    public function refreshStatus(): void
    {
        $items = $this->items()->get();
        $fullyReceived = $items->every(fn ($item) => bccomp($item->quantity_received, $item->quantity_ordered, 3) >= 0);
        $anyReceived = $items->contains(fn ($item) => bccomp($item->quantity_received, '0', 3) > 0);

        $this->status = match (true) {
            $fullyReceived => 'received',
            $anyReceived => 'partially_received',
            default => $this->status === 'draft' ? 'draft' : 'ordered',
        };
        $this->save();
    }
}
