<?php

namespace App\Services;

use App\Models\User;
use App\Services\Contracts\UserManagementServiceContract;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserManagementService implements UserManagementServiceContract
{
    public function authorize(): void
    {
        if (!Auth::check()) {
            throw new NotFoundHttpException();
        }
    }

    public function index(array $search, ?string $sort, int $limit): LengthAwarePaginator
    {
        return User::search($search)
            ->sort($sort)
            ->paginate($limit);
    }

    public function create(array $attributes): User
    {
        $attributes['password'] = Hash::make($attributes['password']);

        return User::create($attributes);
    }

    public function update(User $user, array $attributes): User
    {
        $user->update($attributes);

        return $user;
    }

    public function destroy(User $user): void
    {
        $user->delete();
    }
}
