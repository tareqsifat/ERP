<?php

namespace Modules\Sampling\App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Order\App\Models\Order;
use Modules\Sampling\Database\Factories\SampleFactory;

#[Fillable(['order_id', 'consignee', 'style_number', 'item', 'sample_type', 'quantity', 'status'])]
class Sample extends Model
{
    /** @use HasFactory<SampleFactory> */
    use HasFactory, SoftDeletes;

    protected static function newFactory(): SampleFactory
    {
        return SampleFactory::new();
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
