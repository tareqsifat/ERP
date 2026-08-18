<?php

namespace Modules\User\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\User\App\Http\Requests\UpdateProfileRequest;
use Modules\User\App\Http\Resources\UserResource;

class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // failed_doc.md §2: only name/email/phone/password are ever
        // mass-assigned here — role/location_id/is_active are not in
        // UpdateProfileRequest's rules() at all, so they can't leak
        // through even if a client stuffs them into the request body.
        $user->fill(collect($data)->only(['name', 'email', 'phone'])->toArray());

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return $this->ok(new UserResource($user));
    }
}
