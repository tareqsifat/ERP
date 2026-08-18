<?php

namespace Modules\RawMaterial\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Modules\Location\App\Models\Location;

/**
 * Immutable ledger row. Every row is created exclusively through
 * App\Services\RawMaterialStockService, which is the only place that
 * decides the sign of `quantity` for a given `type` — the #[Fillable]
 * whitelist below is defense-in-depth, not the only guard. There is
 * deliberately no update/destroy route for this model (see
 * Modules/RawMaterial/README.md "Ledger is append-only") — corrections
 * happen by posting a new `adjustment` movement, never by editing
 * history (sdd.md §5's "financial and traceability data should never be
 * hard-deleted" applies here even more strictly: it's never *mutated*
 * either).
 */
#[Fillable(['raw_material_id', 'location_id', 'type', 'quantity', 'reference_type', 'reference_id', 'occurred_on', 'created_by', 'remarks'])]
class RawMaterialStockMovement extends Model
{

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'occurred_on' => 'date',
        ];
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
