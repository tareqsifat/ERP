<?php

namespace Modules\Subcontract\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Location\App\Models\Location;
use Modules\Order\App\Models\Order;
use Modules\Party\App\Models\Party;
use Modules\Production\App\Models\CutTicket;
use Modules\RawMaterial\App\Models\RawMaterial;
use Modules\Subcontract\Database\Factories\SubcontractOrderFactory;

// subcontract_no/year/sequence_no/status/job_work_income_amount/
// dispatched_back_at absent from #[Fillable] — only
// App\Services\SubcontractNumberGenerator (via the controller) and
// App\Services\Subcontract{Outward,Inward}Service write them.
#[Fillable([
    'direction', 'party_id', 'order_id', 'style', 'color', 'size', 'rate', 'rate_unit',
    'quantity_expected', 'raw_material_id', 'raw_material_quantity', 'location_id',
    'expected_date', 'remarks', 'created_by',
])]
class SubcontractOrder extends Model
{
    /** @use HasFactory<SubcontractOrderFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'raw_material_quantity' => 'decimal:3',
            'job_work_income_amount' => 'decimal:2',
            'expected_date' => 'date',
            'dispatched_back_at' => 'datetime',
        ];
    }

    protected static function newFactory(): SubcontractOrderFactory
    {
        return SubcontractOrderFactory::new();
    }

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
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

    public function pieces()
    {
        return $this->hasMany(SubcontractOrderPiece::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(SubcontractLedgerEntry::class);
    }

    // Inward only — Cut Tickets tagged as processing this job (see
    // Modules/Production/database/migrations/
    // ..._add_inward_subcontract_order_id_to_cut_tickets_table).
    public function cutTickets()
    {
        return $this->hasMany(CutTicket::class, 'inward_subcontract_order_id');
    }

    /**
     * Value of `$quantity` pieces at this order's rate — piece rate
     * applies directly, dozen rate divides by 12. bcmath (sdd.md §5)
     * to avoid float drift on money.
     */
    public function valueFor(int $quantity): string
    {
        $rate = (string) $this->rate;

        if ($this->rate_unit === 'dozen') {
            return bcdiv(bcmul($rate, (string) $quantity, 4), '12', 2);
        }

        return bcmul($rate, (string) $quantity, 2);
    }
}
