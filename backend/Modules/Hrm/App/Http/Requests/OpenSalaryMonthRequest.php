<?php

namespace Modules\Hrm\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpenSalaryMonthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:hrm.salary.pay` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->whereNull('deleted_at')],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'min:2000'],
        ];
    }
}
