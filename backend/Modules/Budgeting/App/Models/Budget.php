<?php

namespace Modules\Budgeting\App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Budgeting\Database\Factories\BudgetFactory;
use Modules\Order\App\Models\Order;

/**
 * `total_value` IS in #[Fillable] — see
 * Order/App/Models/OrderLineItem.php's docblock for why (Phase 4
 * correction). It's always server-computed as budgeted_quantity *
 * average_unit_price in BudgetController before create()/fill(); the
 * real defense against a client-sent total is that
 * StoreBudgetRequest/UpdateBudgetRequest never validate that field at
 * all, not this attribute list.
 */
#[Fillable(['order_id', 'style', 'budgeted_quantity', 'average_unit_price', 'status', 'total_value'])]
class Budget extends Model
{
    /** @use HasFactory<BudgetFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'average_unit_price' => 'decimal:2',
            'total_value' => 'decimal:2',
        ];
    }

    protected static function newFactory(): BudgetFactory
    {
        return BudgetFactory::new();
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
