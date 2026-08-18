<?php

namespace Modules\Setting\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Setting\Database\Factories\SettingFactory;

/**
 * One row per key. Only ever written through
 * App\Services\SettingService::set() — see SettingController.
 */
#[Fillable(['key', 'value', 'group', 'updated_by'])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    protected static function newFactory(): SettingFactory
    {
        return SettingFactory::new();
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
