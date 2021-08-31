<?php

namespace App\Services;

use App\Exceptions\AuthException;
use App\Models\Role;
use App\Models\User;
use App\Services\Contracts\UserServiceContract;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserService implements UserServiceContract
{
    public function index(array $search, ?string $sort, int $limit): LengthAwarePaginator
    {
        return User::search($search)
            ->sort($sort)
            ->paginate($limit);
    }

    public function create(string $name, string $email, string $password, array $roles): User
    {
        $roleModels = Role::findMany($roles);

        $permissions = $roleModels->flatMap(
            fn ($role) => $role->getPermissionNames(),
        )->unique();

        if (!Auth::user()->hasAllPermissions($permissions)) {
            throw new AuthException(
                'Can\'t give a role with permissions you don\'t have to the user',
            );
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $user->syncRoles($roleModels);

        return $user;
    }

    public function update(User $user, ?string $name, ?string $email, ?array $roles): User
    {
        if ($roles !== null) {
            $roleModels = Role::findMany($roles);

            $permissions = $roleModels->flatMap(
                fn ($role) => $role->getPermissionNames(),
            )->unique();

            if (!Auth::user()->hasAllPermissions($permissions)) {
                throw new AuthException(
                    'Can\'t give a role with permissions you don\'t have to the user',
                );
            }

            $user->syncRoles($roleModels);
        }

        $user->update(
            [
                'name' => $name ?? $user->name,
                'email' => $email ?? $user->email,
            ]
        );

        return $user;
    }

    public function destroy(User $user): void
    {
        $user->delete();
    }
}
