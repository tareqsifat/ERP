<?php

namespace Modules\RawMaterial\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Party\App\Models\Party;

class UpdateRawMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:raw-material.edit` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'category' => ['sometimes', 'required', Rule::in(['fabric', 'trim', 'packaging', 'other'])],
            'unit' => ['sometimes', 'required', 'string', 'max:50'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'default_supplier_id' => [
                'nullable', 'integer', Rule::exists('parties', 'id')->whereNull('deleted_at'),
                function ($attribute, $value, $fail) {
                    if ($value && Party::find($value)?->type !== 'supplier') {
                        $fail('The default supplier must be a Supplier party.');
                    }
                },
            ],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
