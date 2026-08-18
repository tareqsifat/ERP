<?php

namespace Modules\User\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

/**
 * failed_doc.md §2: role/location assignment is ONLY reachable through
 * this Admin-only, `permission:user.create`-gated endpoint — never via
 * the self-service profile update (see UpdateProfileRequest).
 */
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:user.create` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::in(Role::query()->pluck('name'))],
            'location_id' => ['nullable', 'exists:locations,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
