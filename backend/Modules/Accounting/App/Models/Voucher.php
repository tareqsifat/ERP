<?php

namespace Modules\Accounting\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Accounting\Database\Factories\VoucherFactory;
use Modules\Party\App\Models\Party;

// voucher_no/year/sequence_no absent from #[Fillable] — only
// App\Services\VoucherService::record() (via VoucherNumberGenerator)
// writes them.
#[Fillable([
    'type', 'purpose', 'party_id', 'category_id', 'amount', 'payment_type',
    'bank_account_id', 'cheque_id', 'date', 'bill_no', 'remarks', 'created_by',
])]
class Voucher extends Model
{
    /** @use HasFactory<VoucherFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
        ];
    }

    protected static function newFactory(): VoucherFactory
    {
        return VoucherFactory::new();
    }

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function category()
    {
        return $this->belongsTo(AccountingCategory::class, 'category_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function cheque()
    {
        return $this->belongsTo(Cheque::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeCredit($query)
    {
        return $query->where('type', 'credit');
    }

    public function scopeDebit($query)
    {
        return $query->where('type', 'debit');
    }
}
