<?php

namespace Modules\Location\App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Location\Database\Factories\LocationFactory;

#[Fillable(['name', 'type', 'address', 'is_active'])]
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function newFactory(): LocationFactory
    {
        return LocationFactory::new();
    }

    public function scopeFactories($query)
    {
        return $query->where('type', 'factory');
    }

    public function scopeStores($query)
    {
        return $query->where('type', 'store');
    }

    public function scopeShowrooms($query)
    {
        return $query->where('type', 'showroom');
    }
}
