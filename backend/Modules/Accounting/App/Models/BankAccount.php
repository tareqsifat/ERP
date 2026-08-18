<?php

namespace Modules\Accounting\App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Accounting\Database\Factories\BankAccountFactory;

// Balance deliberately not a column here — see the bank_accounts
// migration's docblock. Read it via App\Services\BankLedgerService::balanceOf().
#[Fillable(['account_holder_name', 'bank_name', 'account_number', 'branch_name', 'routing_swift_no', 'is_active'])]
class BankAccount extends Model
{
    /** @use HasFactory<BankAccountFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function newFactory(): BankAccountFactory
    {
        return BankAccountFactory::new();
    }

    public function transactions()
    {
        return $this->hasMany(BankTransaction::class);
    }
}
