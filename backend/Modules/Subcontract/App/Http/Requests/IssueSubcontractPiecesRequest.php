<?php

namespace Modules\Subcontract\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IssueSubcontractPiecesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:subcontract.outward.manage` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'piece_serial_ids' => ['required', 'array', 'min:1'],
            'piece_serial_ids.*' => ['integer', Rule::exists('piece_serials', 'id')->whereNull('deleted_at')],
        ];
    }
}
