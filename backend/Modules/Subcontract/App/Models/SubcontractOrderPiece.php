<?php

namespace Modules\Subcontract\App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Production\App\Models\PieceSerial;
use Modules\Subcontract\Database\Factories\SubcontractOrderPieceFactory;

// resolution/resolved_at absent from #[Fillable] — only
// App\Services\SubcontractOutwardService::returnPieces() writes them;
// this row's own creation (issue) only ever sets issued_at.
#[Fillable(['subcontract_order_id', 'piece_serial_id', 'issued_at'])]
class SubcontractOrderPiece extends Model
{
    /** @use HasFactory<SubcontractOrderPieceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    protected static function newFactory(): SubcontractOrderPieceFactory
    {
        return SubcontractOrderPieceFactory::new();
    }

    public function subcontractOrder()
    {
        return $this->belongsTo(SubcontractOrder::class);
    }

    public function pieceSerial()
    {
        return $this->belongsTo(PieceSerial::class);
    }

    public function scopeOutstanding($query)
    {
        return $query->whereNull('resolution');
    }
}
