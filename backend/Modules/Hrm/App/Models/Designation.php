<?php

namespace Modules\Hrm\App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Hrm\Database\Factories\DesignationFactory;

#[Fillable(['name', 'description', 'is_active'])]
class Designation extends Model
{
    /** @use HasFactory<DesignationFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function newFactory(): DesignationFactory
    {
        return DesignationFactory::new();
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
