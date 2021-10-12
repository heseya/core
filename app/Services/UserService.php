<?php

namespace App\Services;

use App\Enums\RoleType;
use App\Exceptions\AuthException;
use App\Models\Role;
use App\Models\User;
use App\Services\Contracts\UserServiceContract;
use Illuminate\Database\Eloquent\Builder;
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
        $authenticable = Auth::user();

        if ($roles !== null) {
            $roleModels = Role::findMany($roles);

            $newRoles = $roleModels->diff($user->roles);
            $removedRoles = $user->roles->diff($roleModels);

            $permissions = $newRoles->flatMap(
                fn ($role) => $role->getPermissionNames(),
            )->unique();

            if (!$authenticable->hasAllPermissions($permissions)) {
                throw new AuthException(
                    'Can\'t give a role with permissions you don\'t have to the user',
                );
            }

            $permissions = $removedRoles->flatMap(
                fn ($role) => $role->getPermissionNames(),
            )->unique();

            if (!$authenticable->hasAllPermissions($permissions)) {
                throw new AuthException(
                    'Can\'t remove a role with permissions you don\'t have from the user',
                );
            }

            $owner = Role::where('type', RoleType::OWNER)->first();

            if (
                $newRoles->contains($owner) && (!($authenticable instanceof User) ||
                    !$authenticable->hasRole($owner))) {
                throw new AuthException('Only owner can grant the owner role');
            }

            if ($removedRoles->contains($owner)) {
                if (!($authenticable instanceof User) || !$authenticable->hasRole($owner)) {
                    throw new AuthException('Only owner can remove the owner role');
                }

                $ownerCount = User::whereHas(
                    'roles',
                    fn (Builder $query) => $query->where('type', RoleType::OWNER),
                )->count();

                if ($ownerCount < 2) {
                    throw new AuthException('There must always be at least one Owner left');
                }
            }

            $user->syncRoles($roleModels);
        }

        $user->update([
            'name' => $name ?? $user->name,
            'email' => $email ?? $user->email,
        ]);

        return $user;
    }

    public function destroy(User $user): void
    {
        $authenticable = Auth::user();

        $owner = Role::where('type', RoleType::OWNER)->first();

        if ($user->hasRole($owner)) {
            if (!($authenticable instanceof User) || !$authenticable->hasRole($owner)) {
                throw new AuthException(
                    'You need to be an Owner to delete the Owner.',
                );
            }

            $ownerCount = User::whereHas(
                'roles',
                fn (Builder $query) => $query->where('type', RoleType::OWNER),
            )->count();

            if ($ownerCount < 2) {
                throw new AuthException(
                    'There must always be at least one Owner left',
                );
            }
        }

        $user->delete();
    }
}
