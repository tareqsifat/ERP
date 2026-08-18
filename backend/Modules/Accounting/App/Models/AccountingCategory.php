<?php

namespace Modules\Accounting\App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Accounting\Database\Factories\AccountingCategoryFactory;

#[Fillable(['kind', 'name', 'description', 'is_active'])]
class AccountingCategory extends Model
{
    /** @use HasFactory<AccountingCategoryFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function newFactory(): AccountingCategoryFactory
    {
        return AccountingCategoryFactory::new();
    }

    public function scopeIncome($query)
    {
        return $query->where('kind', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('kind', 'expense');
    }
}
