<?php

namespace Modules\Hrm\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Hrm\Database\Factories\EmployeeFactory;

#[Fillable([
    'full_name', 'phone', 'gender', 'employment_type', 'birth_date', 'joining_date',
    'designation_id', 'salary', 'id_document_path', 'id_document_back_path', 'status',
])]
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'joining_date' => 'date',
            'salary' => 'decimal:2',
        ];
    }

    protected static function newFactory(): EmployeeFactory
    {
        return EmployeeFactory::new();
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function salaryPayments()
    {
        return $this->hasMany(SalaryPayment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
