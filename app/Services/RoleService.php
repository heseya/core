<?php

namespace App\Services;

use App\Dtos\RoleCreateDto;
use App\Dtos\RoleSearchDto;
use App\Dtos\RoleUpdateDto;
use App\Enums\RoleType;
use App\Exceptions\AuthException;
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
        return Role::search($searchDto->toArray())->paginate($limit);
    }

    public function create(RoleCreateDto $dto): Role
    {
        if (!Auth::user()->hasAllPermissions($dto->getPermissions())) {
            throw new AuthException(
                'Cant create a role with permissions you don\'t have',
            );
        }

        $role = Role::create($dto->toArray());
        $role->syncPermissions($dto->getPermissions());
        $role->refresh();

        return $role;
    }

    public function update(Role $role, RoleUpdateDto $dto): Role
    {
        if (!Auth::user()->hasAllPermissions($role->getAllPermissions())) {
            throw new AuthException(
                'Cant update a role with permissions you don\'t have',
            );
        }

        if (!$dto->getPermissions() instanceof Missing) {
            if (
                $role->type->is(RoleType::OWNER) &&
                $role->getPermissionNames()->diff($dto->getPermissions())->isNotEmpty()
            ) {
                throw new RoleException('Can\'t update owners permissions');
            }

            if (!Auth::user()->hasAllPermissions($dto->getPermissions())) {
                throw new AuthException(
                    'Cant update a role with permissions you don\'t have',
                );
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
            throw new RoleException('Can\'t delete built-in roles');
        }

        if (!Auth::user()->hasAllPermissions($role->getAllPermissions())) {
            throw new AuthException(
                'Cant delete a role with permissions you don\'t have',
            );
        }

        $role->delete();
    }
}
