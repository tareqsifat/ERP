<?php

namespace Modules\Subcontract\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IssueSubcontractRawMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:subcontract.outward.manage` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'cut_date' => ['required', 'date'],
            'cutting_master_id' => ['required', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'bundle_size' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
