<?php

namespace Modules\Party\App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Party\Database\Factories\PartyFactory;

/**
 * PRD v1 §6.3 / PRD v2 §4.9. `total_bill`/`advance`/`paid`/`due`/`balance`
 * shown in the PRD as columns are deliberately NOT stored here — sdd.md §5's
 * "ledger is the source of truth, not a mutable column that drifts"
 * principle applies to money the same way it applies to stock. Those
 * figures will be computed from Modules/Accounting vouchers once that
 * module exists (Phase 6); see this module's README "Known gaps".
 * `opening_balance` IS stored because it is a real one-time input at
 * party creation, not a running total.
 */
#[Fillable(['name', 'type', 'email', 'phone', 'address', 'country', 'opening_balance_type', 'opening_balance', 'remarks', 'image_path', 'is_active'])]
class Party extends Model
{
    /** @use HasFactory<PartyFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): PartyFactory
    {
        return PartyFactory::new();
    }

    public function scopeBuyers($query)
    {
        return $query->where('type', 'buyer');
    }

    public function scopeSuppliers($query)
    {
        return $query->where('type', 'supplier');
    }

    public function scopeSubcontractors($query)
    {
        return $query->where('type', 'subcontractor');
    }
}
