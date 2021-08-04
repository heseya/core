<?php

namespace App\Services;

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

    public function create(string $name, string $email, string $password): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);
    }

    public function update(User $user, ?string $name, ?string $email): User
    {
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
