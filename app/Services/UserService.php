<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Services\Contracts\UserServiceContract;
use Illuminate\Pagination\LengthAwarePaginator;
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
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $roleModels = Role::findMany($roles);
        $user->syncRoles($roleModels);

        return $user;
    }

    public function update(User $user, ?string $name, ?string $email, ?array $roles): User
    {
        $user->update(
            [
                'name' => $name ?? $user->name,
                'email' => $email ?? $user->email,
            ]
        );

        if ($roles !== null) {
            $roleModels = Role::findMany($roles);
            $user->syncRoles($roleModels);
        }

        return $user;
    }

    public function destroy(User $user): void
    {
        $user->delete();
    }
}
