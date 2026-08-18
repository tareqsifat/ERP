<?php

namespace Modules\Setting\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by the 'setting.manage' route middleware
    }

    public function rules(): array
    {
        return [
            'group' => ['required', Rule::in(['currency', 'notification', 'system', 'company'])],
            'values' => ['required', 'array', 'min:1'],
        ];
    }
}
