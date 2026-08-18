<?php

namespace Modules\RawMaterial\App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Location\App\Models\Location;
use Modules\Party\App\Models\Party;
use Modules\RawMaterial\Database\Factories\RawMaterialFactory;

#[Fillable(['name', 'category', 'unit', 'reorder_level', 'default_supplier_id', 'unit_cost', 'is_active'])]
class RawMaterial extends Model
{
    /** @use HasFactory<RawMaterialFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'reorder_level' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): RawMaterialFactory
    {
        return RawMaterialFactory::new();
    }

    public function defaultSupplier()
    {
        return $this->belongsTo(Party::class, 'default_supplier_id');
    }

    public function movements()
    {
        return $this->hasMany(RawMaterialStockMovement::class);
    }

    /**
     * sdd.md §5: computed from the ledger, never stored. Pass a Location
     * to scope to one site, or omit for the total across all sites.
     */
    public function stockOn(?Location $location = null): string
    {
        $query = $this->movements();
        if ($location) {
            $query->where('location_id', $location->id);
        }

        return (string) $query->sum('quantity');
    }

    public function isBelowReorderLevel(): bool
    {
        return (float) $this->stockOn() <= (float) $this->reorder_level;
    }
}
