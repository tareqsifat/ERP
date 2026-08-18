<?php

namespace Modules\Hrm\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Hrm\Database\Factories\SalaryPaymentFactory;

// paid_amount/payment_method/pay_date absent from #[Fillable] — only
// App\Services\SalaryService::pay() writes them; this row's own
// creation (App\Services\SalaryService::openMonth()) only ever sets
// employee_id/month/year/salary_amount/created_by.
#[Fillable(['employee_id', 'month', 'year', 'salary_amount', 'created_by'])]
class SalaryPayment extends Model
{
    /** @use HasFactory<SalaryPaymentFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'salary_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'pay_date' => 'date',
        ];
    }

    protected static function newFactory(): SalaryPaymentFactory
    {
        return SalaryPaymentFactory::new();
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Derived, never stored — see the salary_payments migration's docblock.
    public function getDueAmountAttribute(): string
    {
        return bcsub((string) $this->salary_amount, (string) $this->paid_amount, 2);
    }
}
