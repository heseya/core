<?php

namespace App\Services;

use App\Dtos\UserDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\RoleType;
use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Exceptions\ClientException;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\UserServiceContract;
use Heseya\Dto\Missing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;

class UserService implements UserServiceContract
{
    public function __construct(private MetadataServiceContract $metadataService)
    {
    }

    public function index(array $search, ?string $sort): LengthAwarePaginator
    {
        return User::searchByCriteria($search)
            ->sort($sort)
            ->with('metadata')
            ->paginate(Config::get('pagination.per_page'));
    }

    public function create(UserDto $dto): User
    {
        if (!$dto->getRoles() instanceof Missing) {
            $roleModels = Role::whereIn('id', $dto->getRoles())->orWhere('type', RoleType::AUTHENTICATED)->get();
        } else {
            $roleModels = Role::where('type', RoleType::AUTHENTICATED)->get();
        }

        $permissions = $roleModels->flatMap(
            fn ($role) => $role->type->value !== RoleType::AUTHENTICATED ? $role->getPermissionNames() : [],
        )->unique();

        if (!Auth::user()->hasAllPermissions($permissions)) {
            throw new ClientException(Exceptions::CLIENT_GIVE_ROLE_THAT_USER_DOESNT_HAVE, simpleLogs: true);
        }

        $fields = $dto->toArray();
        $fields['password'] = Hash::make($dto->getPassword());
        $user = User::create($fields);

        $preferences = UserPreference::create();
        $preferences->refresh();

        $user->preferences()->associate($preferences);

        $user->syncRoles($roleModels);

        if (!($dto->getMetadata() instanceof Missing)) {
            $this->metadataService->sync($user, $dto->getMetadata());
        }

        $user->save();

        UserCreated::dispatch($user);

        return $user;
    }

    public function update(User $user, UserDto $dto): User
    {
        $authenticable = Auth::user();

        if (!$dto->getRoles() instanceof Missing && $dto->getRoles() !== null) {
            /** @var Collection<int, Role> $roleModels */
            $roleModels = Role::whereIn('id', $dto->getRoles())->orWhere('type', RoleType::AUTHENTICATED)->get();

            $newRoles = $roleModels->diff($user->roles);
            /** @var Collection<int, Role> $removedRoles */
            $removedRoles = $user->roles->diff($roleModels);

            $permissions = $newRoles->flatMap(
                fn ($role) => $role->type->value !== RoleType::AUTHENTICATED ? $role->getPermissionNames() : [],
            )->unique();

            if (!$authenticable->hasAllPermissions($permissions)) {
                throw new ClientException(Exceptions::CLIENT_GIVE_ROLE_THAT_USER_DOESNT_HAVE);
            }

            $permissions = $removedRoles->flatMap(
                fn (Role $role) => $role->type->value !== RoleType::AUTHENTICATED ? $role->getPermissionNames() : [],
            )->unique();

            if (!$authenticable->hasAllPermissions($permissions)) {
                throw new ClientException(Exceptions::CLIENT_REMOVE_ROLE_THAT_USER_DOESNT_HAVE);
            }

            $owner = Role::where('type', RoleType::OWNER)->first();

            if ($newRoles->contains($owner) && !$authenticable->hasRole($owner)) {
                throw new ClientException(Exceptions::CLIENT_ONLY_OWNER_GRANTS_OWNER_ROLE);
            }

            if ($removedRoles->contains($owner)) {
                if (!$authenticable->hasRole($owner)) {
                    throw new ClientException(Exceptions::CLIENT_ONLY_OWNER_REMOVES_OWNER_ROLE);
                }

                $ownerCount = User::whereHas(
                    'roles',
                    fn (Builder $query) => $query->where('type', RoleType::OWNER),
                )->count();

                if ($ownerCount < 2) {
                    throw new ClientException(Exceptions::CLIENT_ONE_OWNER_REMAINS);
                }
            }

            $user->syncRoles($roleModels);
        }

        $user->update($dto->toArray());

        UserUpdated::dispatch($user);

        return $user;
    }

    public function destroy(User $user): void
    {
        $authenticable = Auth::user();

        $owner = Role::where('type', RoleType::OWNER)->first();

        if ($user->hasRole($owner)) {
            if (!$authenticable->hasRole($owner)) {
                throw new ClientException(Exceptions::CLIENT_ONLY_OWNER_REMOVES_OWNER_ROLE);
            }

            $ownerCount = User::whereHas(
                'roles',
                fn (Builder $query) => $query->where('type', RoleType::OWNER),
            )->count();

            if ($ownerCount < 2) {
                throw new ClientException(Exceptions::CLIENT_ONE_OWNER_REMAINS);
            }
        }

        if ($user->delete()) {
            UserDeleted::dispatch($user);
        }
    }
}
