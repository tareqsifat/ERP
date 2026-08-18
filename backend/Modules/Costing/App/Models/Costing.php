<?php

namespace Modules\Costing\App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Costing\Database\Factories\CostingFactory;
use Modules\Order\App\Models\Order;

// `total_cost` IS in #[Fillable] — see Order/App/Models/OrderLineItem.php's
// docblock for why (Phase 4 correction). Real defense is that
// Store/UpdateCostingRequest never validate a client-sent total_cost.
#[Fillable(['order_id', 'style', 'costed_quantity', 'average_unit_cost', 'status', 'total_cost'])]
class Costing extends Model
{
    /** @use HasFactory<CostingFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'average_unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
        ];
    }

    protected static function newFactory(): CostingFactory
    {
        return CostingFactory::new();
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
