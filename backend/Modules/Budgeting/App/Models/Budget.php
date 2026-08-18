<?php

namespace Modules\Budgeting\App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Budgeting\Database\Factories\BudgetFactory;
use Modules\Order\App\Models\Order;

/**
 * `total_value` is absent from #[Fillable] — always server-computed as
 * budgeted_quantity * average_unit_price (BudgetController), never
 * trusted from client input.
 */
#[Fillable(['order_id', 'style', 'budgeted_quantity', 'average_unit_price', 'status'])]
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
