<?php

namespace Modules\Order\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Order\Database\Factories\OrderFactory;
use Modules\Party\App\Models\Party;

/**
 * PRD v1 §6.1. `order_no` is deliberately absent from #[Fillable] — it is
 * only ever set by OrderNumberGenerator (see App/Services), never by mass
 * assignment from a request, so a client can never collide/spoof it.
 * `grand_total` is likewise absent — only OrderController's
 * recalculateGrandTotal() writes it, always derived from
 * order_line_items (sdd.md §5).
 */
#[Fillable([
    'party_id', 'merchandiser_id', 'item_image_path', 'title', 'description',
    'fabrication', 'gsm', 'yarn_count', 'shipment_mode', 'payment_mode',
    'bank_account_name', 'year', 'season', 'pantone', 'consignee', 'notify_party',
    'contract_date', 'expiry_date', 'negotiation_period_days', 'port_of_loading',
    'port_of_discharge', 'payment_terms', 'remarks', 'status',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'contract_date' => 'date',
            'expiry_date' => 'date',
            'grand_total' => 'decimal:2',
        ];
    }

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function merchandiser()
    {
        return $this->belongsTo(User::class, 'merchandiser_id');
    }

    public function lineItems()
    {
        return $this->hasMany(OrderLineItem::class);
    }

    /**
     * sdd.md §5: recomputed from order_line_items (the source of truth),
     * never accepted directly from client input. Called inside the same
     * DB transaction as any line-item create/update/delete.
     */
    public function recalculateGrandTotal(): void
    {
        $this->grand_total = $this->lineItems()->sum('total_price');
        $this->save();
    }
}
