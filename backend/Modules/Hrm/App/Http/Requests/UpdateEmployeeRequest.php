<?php

namespace Modules\Hrm\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:hrm.employee.manage` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'full_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'employment_type' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'joining_date' => ['sometimes', 'date'],
            'designation_id' => ['sometimes', 'integer', Rule::exists('designations', 'id')->whereNull('deleted_at')],
            'salary' => ['sometimes', 'numeric', 'min:0.01'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            'id_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
            'id_document_back' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ];
    }
}
