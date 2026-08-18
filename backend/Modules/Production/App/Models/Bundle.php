<?php

namespace Modules\Production\App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Production\Database\Factories\BundleFactory;

/**
 * Never created directly from a request — always via
 * App\Services\CuttingService (creation, on Cut Ticket finalize) or
 * App\Services\SewingService (line assignment/output). No controller
 * ever exposes a store/update route for this model (see
 * Modules/Production/routes/api.php) — the #[Fillable] list below is
 * what the Services use internally, not a client-facing contract.
 */
#[Fillable(['cut_ticket_id', 'bundle_no', 'quantity', 'line_id', 'status', 'assigned_to_line_at', 'line_output_at'])]
class Bundle extends Model
{
    /** @use HasFactory<BundleFactory> */
    use HasFactory, SoftDeletes;

    protected static function newFactory(): BundleFactory
    {
        return BundleFactory::new();
    }

    protected function casts(): array
    {
        return [
            'assigned_to_line_at' => 'datetime',
            'line_output_at' => 'datetime',
        ];
    }

    public function cutTicket()
    {
        return $this->belongsTo(CutTicket::class);
    }

    public function line()
    {
        return $this->belongsTo(Line::class);
    }

    public function pieceSerials()
    {
        return $this->hasMany(PieceSerial::class);
    }
}
