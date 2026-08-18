<?php

namespace Modules\Hrm\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:hrm.employee.manage` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'employment_type' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'joining_date' => ['required', 'date'],
            'designation_id' => ['required', 'integer', Rule::exists('designations', 'id')->whereNull('deleted_at')],
            'salary' => ['required', 'numeric', 'min:0.01'],
            // sdd.md §8: validated by MIME type and size server-side, never
            // trust the client extension — same contract as Party's image.
            'id_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
            'id_document_back' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ];
    }
}
