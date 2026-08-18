<?php

namespace Modules\User\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * failed_doc.md §2: deliberately has NO `role`, `location_id`, or
 * `is_active` field — a user can never escalate their own role/location
 * by stuffing extra keys into a profile-update body, because those keys
 * simply aren't in $rules() and the controller only mass-assigns
 * validated() data.
 */
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // any authenticated user may update their own profile
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
        ];
    }
}
