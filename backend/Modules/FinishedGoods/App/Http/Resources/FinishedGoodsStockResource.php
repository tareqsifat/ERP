<?php

namespace Modules\FinishedGoods\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A grouped ledger aggregate ({location, order, style, color, size} =>
 * sum(quantity)), not an Eloquent model — see
 * FinishedGoodsController@stock.
 */
class FinishedGoodsStockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'location_id' => (int) $this->location_id,
            'order_id' => (int) $this->order_id,
            'style' => $this->style,
            'color' => $this->color,
            'size' => $this->size,
            'quantity' => (int) $this->quantity,
        ];
    }
}
