<?php

namespace Modules\User\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\User\App\Http\Requests\StoreUserRequest;
use Modules\User\App\Http\Requests\UpdateUserRequest;
use Modules\User\App\Http\Resources\UserResource;

/**
 * failed_doc.md §2: role is assigned here — an Admin-only,
 * `permission:user.create`/`user.edit`-gated surface — and nowhere else.
 */
class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // failed_doc.md §10: page size is capped, never client-unbounded.
        $perPage = min((int) $request->integer('per_page', 20), 100);

        $users = User::query()
            ->when($request->filled('role'), fn ($q) => $q->role($request->string('role')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($q2) => $q2->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->orderBy('name')
            ->paginate($perPage);

        return $this->ok(UserResource::collection($users));
    }

    public function show(User $user): JsonResponse
    {
        return $this->ok(new UserResource($user));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'location_id' => $data['location_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $user->assignRole($data['role']);

        return $this->created(new UserResource($user));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        $user->fill(collect($data)->only(['name', 'email', 'phone', 'location_id', 'is_active'])->toArray());

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        if (! empty($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return $this->ok(new UserResource($user));
    }

    public function destroy(User $user): JsonResponse
    {
        // sdd.md §5: soft delete only — Users are referenced by Orders,
        // Cut Tickets, vouchers, etc.
        $user->delete();

        return $this->noContent();
    }
}
