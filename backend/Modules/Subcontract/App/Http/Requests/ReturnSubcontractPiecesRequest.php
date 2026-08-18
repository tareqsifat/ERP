<?php

namespace Modules\Subcontract\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReturnSubcontractPiecesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:subcontract.outward.manage` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'returned_piece_serial_ids' => ['array'],
            'returned_piece_serial_ids.*' => ['integer', Rule::exists('piece_serials', 'id')->whereNull('deleted_at')],
            'written_off_piece_serial_ids' => ['array'],
            'written_off_piece_serial_ids.*' => ['integer', Rule::exists('piece_serials', 'id')->whereNull('deleted_at')],
        ];
    }
}
