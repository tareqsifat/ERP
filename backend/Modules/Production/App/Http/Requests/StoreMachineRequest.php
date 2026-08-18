<?php

namespace Modules\Production\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMachineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:machine.create` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'tag' => ['required', 'string', 'max:255', 'unique:machines,tag'],
            'type' => ['required', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['active', 'maintenance', 'idle'])],
            'line_id' => ['nullable', 'integer', Rule::exists('lines', 'id')->whereNull('deleted_at')],
        ];
    }
}
