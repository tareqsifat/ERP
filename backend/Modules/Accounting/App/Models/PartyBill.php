<?php

namespace Modules\Accounting\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Accounting\Database\Factories\PartyBillFactory;
use Modules\Party\App\Models\Party;

// Append-only — see the party_bills migration's docblock.
#[Fillable(['party_id', 'amount', 'bill_date', 'description', 'reference', 'created_by'])]
class PartyBill extends Model
{
    /** @use HasFactory<PartyBillFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'bill_date' => 'date',
        ];
    }

    protected static function newFactory(): PartyBillFactory
    {
        return PartyBillFactory::new();
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
