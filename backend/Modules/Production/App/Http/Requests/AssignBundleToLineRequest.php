<?php

namespace Modules\Production\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignBundleToLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:production.sewing.create` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'line_id' => ['required', 'integer', Rule::exists('lines', 'id')->whereNull('deleted_at')],
        ];
    }
}
