<?php

namespace Modules\Production\App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Production\Database\Factories\LineFactory;

#[Fillable(['name', 'capacity', 'is_active'])]
class Line extends Model
{
    /** @use HasFactory<LineFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function newFactory(): LineFactory
    {
        return LineFactory::new();
    }

    public function machines()
    {
        return $this->hasMany(Machine::class);
    }

    public function bundles()
    {
        return $this->hasMany(Bundle::class);
    }
}
