<?php

namespace Modules\Production\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMachineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:machine.edit` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'tag' => ['sometimes', 'string', 'max:255', Rule::unique('machines', 'tag')->ignore($this->route('machine'))],
            'type' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['active', 'maintenance', 'idle'])],
            'line_id' => ['nullable', 'integer', Rule::exists('lines', 'id')->whereNull('deleted_at')],
        ];
    }
}
