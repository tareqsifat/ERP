<?php

namespace Modules\RawMaterial\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RawMaterialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'unit' => $this->unit,
            'reorder_level' => $this->reorder_level,
            'default_supplier_id' => $this->default_supplier_id,
            'unit_cost' => $this->unit_cost,
            'is_active' => $this->is_active,
            // Only computed when explicitly requested (RawMaterialController@index/show
            // pass `with_stock=1`) — summing the ledger on every list row would be
            // an N+1 query storm; opt-in keeps the default list endpoint cheap.
            'current_stock' => $this->when(isset($this->current_stock), fn () => $this->current_stock),
            'created_at' => $this->created_at,
        ];
    }
}
