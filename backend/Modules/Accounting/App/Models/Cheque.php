<?php

namespace Modules\Accounting\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Accounting\Database\Factories\ChequeFactory;
use Modules\Party\App\Models\Party;

// status/passed_at absent from #[Fillable] — only
// App\Services\ChequeService::markPassed() writes them.
#[Fillable(['party_id', 'bank_account_id', 'cheque_no', 'amount', 'issue_date', 'type', 'remarks', 'created_by'])]
class Cheque extends Model
{
    /** @use HasFactory<ChequeFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'issue_date' => 'date',
            'passed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ChequeFactory
    {
        return ChequeFactory::new();
    }

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeUnused($query)
    {
        return $query->where('status', 'unused');
    }

    public function scopePassed($query)
    {
        return $query->where('status', 'passed');
    }
}
