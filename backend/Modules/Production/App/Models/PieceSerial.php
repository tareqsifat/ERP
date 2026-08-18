<?php

namespace Modules\Production\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Order\App\Models\Order;
use Modules\Production\Database\Factories\PieceSerialFactory;

/**
 * The traceability spine (sdd.md §5). Created exclusively by
 * App\Services\CuttingService (serial generation on Cut Ticket
 * finalize) and mutated exclusively by App\Services\SewingService /
 * App\Services\QcService — no controller exposes a store/update route
 * for this model (see Modules/Production/routes/api.php); the
 * #[Fillable] list is what those Services use internally, not a
 * client-facing contract.
 */
#[Fillable(['bundle_id', 'order_id', 'serial', 'status', 'qc_reject_reason', 'qc_by', 'qc_at'])]
class PieceSerial extends Model
{
    /** @use HasFactory<PieceSerialFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['qc_at' => 'datetime'];
    }

    protected static function newFactory(): PieceSerialFactory
    {
        return PieceSerialFactory::new();
    }

    public function bundle()
    {
        return $this->belongsTo(Bundle::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function qcBy()
    {
        return $this->belongsTo(User::class, 'qc_by');
    }
}
