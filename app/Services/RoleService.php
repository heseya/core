<?php

namespace App\Services;

use App\Dtos\RoleCreateDto;
use App\Dtos\RoleSearchDto;
use App\Dtos\RoleUpdateDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\RoleType;
use App\Exceptions\AuthException;
use App\Exceptions\ClientException;
use App\Exceptions\RoleException;
use App\Models\Role;
use App\Services\Contracts\RoleServiceContract;
use Heseya\Dto\Missing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class RoleService implements RoleServiceContract
{
    public function search(RoleSearchDto $searchDto, int $limit): LengthAwarePaginator
    {
        return Role::searchByCriteria($searchDto->toArray())->paginate($limit);
    }

    public function create(RoleCreateDto $dto): Role
    {
        if (!Auth::user()->hasAllPermissions($dto->getPermissions())) {
            throw new ClientException(Exceptions::CLIENT_CREATE_ROLE_WITHOUT_PERMISSION);
        }

        $role = Role::create($dto->toArray());
        $role->syncPermissions($dto->getPermissions());
        $role->refresh();

        return $role;
    }

    public function update(Role $role, RoleUpdateDto $dto): Role
    {
        if (!Auth::user()->hasAllPermissions($role->getAllPermissions())) {
            throw new ClientException(Exceptions::CLIENT_UPDATE_ROLE_WITHOUT_PERMISSION);
        }

        if (!$dto->getPermissions() instanceof Missing) {
            if (
                $role->type->is(RoleType::OWNER) &&
                $role->getPermissionNames()->diff($dto->getPermissions())->isNotEmpty()
            ) {
                throw new ClientException(Exceptions::CLIENT_UPDATE_OWNER_PERMISSION);
            }

            if (!Auth::user()->hasAllPermissions($dto->getPermissions())) {
                throw new ClientException(Exceptions::CLIENT_UPDATE_ROLE_WITHOUT_PERMISSION);
            }

            $role->syncPermissions($dto->getPermissions());
        }

        $role->update($dto->toArray());

        return $role;
    }

    public function delete(Role $role): void
    {
        if (
            $role->type->is(RoleType::OWNER) ||
            $role->type->is(RoleType::UNAUTHENTICATED) ||
            $role->type->is(RoleType::AUTHENTICATED)
        ) {
            throw new ClientException(Exceptions::CLIENT_DELETE_BUILT_IN_ROLE);
        }

        if (!Auth::user()->hasAllPermissions($role->getAllPermissions())) {
            throw new ClientException(Exceptions::CLIENT_DELETE_ROLE_WITHOUT_PERMISSION);
        }

        $role->delete();
    }
}
