<?php

namespace Modules\Production\App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Production\Database\Factories\MachineFactory;

#[Fillable(['tag', 'type', 'status', 'line_id'])]
class Machine extends Model
{
    /** @use HasFactory<MachineFactory> */
    use HasFactory, SoftDeletes;

    protected static function newFactory(): MachineFactory
    {
        return MachineFactory::new();
    }

    public function line()
    {
        return $this->belongsTo(Line::class);
    }
}
