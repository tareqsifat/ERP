<?php

namespace Modules\Subcontract\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Party\App\Models\Party;
use Modules\Subcontract\Database\Factories\SubcontractLedgerEntryFactory;

/**
 * Append-only — no update/destroy route, same contract as
 * RawMaterialStockMovement/FinishedGoodsMovement (sdd.md §5). Only ever
 * created through App\Services\SubcontractLedgerService.
 */
#[Fillable(['subcontract_order_id', 'party_id', 'type', 'amount', 'occurred_on', 'remarks', 'created_by'])]
class SubcontractLedgerEntry extends Model
{
    /** @use HasFactory<SubcontractLedgerEntryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'occurred_on' => 'date',
        ];
    }

    protected static function newFactory(): SubcontractLedgerEntryFactory
    {
        return SubcontractLedgerEntryFactory::new();
    }

    public function subcontractOrder()
    {
        return $this->belongsTo(SubcontractOrder::class);
    }

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
